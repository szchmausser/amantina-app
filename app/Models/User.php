<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, InteractsWithMedia, Notifiable, SoftDeletes, TwoFactorAuthenticatable;

    use HasRoles {
        HasRoles::hasPermissionTo as protected spatieHasPermissionTo;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'cedula',
        'phone',
        'address',
        'is_active',
        'is_transfer',
        'institution_origin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected $appends = [
        'avatar_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_active' => 'boolean',
            'is_transfer' => 'boolean',
        ];
    }

    /**
     * Check if the given role matches the active session role.
     */
    public function hasActiveRole(string $role): bool
    {
        return Session::get('active_role') === $role;
    }

    /**
     * Override Spatie's hasPermissionTo to scope permissions to the active role.
     * When a user logs in with a specific role context, only that role's
     * permissions are considered for authorization.
     */
    public function hasPermissionTo($permission, $guardName = null): bool
    {
        $activeRole = Session::get('active_role');

        if ($activeRole && $this->hasRole($activeRole)) {
            try {
                $role = Role::findByName($activeRole, $guardName ?? 'web');

                if ($role->hasPermissionTo($permission)) {
                    return true;
                }
            } catch (PermissionDoesNotExist $e) {
                Log::warning('PermissionDoesNotExist in hasPermissionTo (active role)', [
                    'permission' => is_string($permission) ? $permission : gettype($permission),
                    'active_role' => $activeRole,
                    'user_id' => $this->id,
                ]);
                // Permission not found in DB, continue to check direct permissions
            }

            // Also check direct permissions assigned outside of roles
            if ($this->getDirectPermissions()->pluck('name')->contains($permission)) {
                return true;
            }

            return false;
        }

        try {
            return $this->spatieHasPermissionTo($permission, $guardName);
        } catch (PermissionDoesNotExist $e) {
            Log::warning('PermissionDoesNotExist in hasPermissionTo fallback', [
                'permission' => is_string($permission) ? $permission : gettype($permission),
                'user_id' => $this->id,
                'context' => 'fallback_no_active_role',
            ]);

            return false;
        }
    }

    /**
     * Check permission explicitly for a given role, independent of session state.
     * Use in jobs, commands, notifications, and contexts without active HTTP session.
     */
    public function hasPermissionForRole(string $permission, string $role, ?string $guardName = null): bool
    {
        if (! $this->hasRole($role)) {
            return false;
        }

        try {
            $roleModel = Role::findByName($role, $guardName ?? 'web');

            return $roleModel->hasPermissionTo($permission)
                || $this->getDirectPermissions()->pluck('name')->contains($permission);
        } catch (PermissionDoesNotExist $e) {
            Log::warning('PermissionDoesNotExist in hasPermissionForRole', [
                'permission' => $permission,
                'role' => $role,
                'user_id' => $this->id,
            ]);

            return false;
        }
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function teacherAssignments(): HasMany
    {
        return $this->hasMany(TeacherAssignment::class);
    }

    /**
     * Get the representatives for the student.
     */
    public function representatives(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'student_representatives', 'student_id', 'representative_id')
            ->withPivot('relationship_type_id')
            ->withTimestamps();
    }

    /**
     * Get the students represented by the user.
     */
    public function representedStudents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'student_representatives', 'representative_id', 'student_id')
            ->withPivot('relationship_type_id')
            ->withTimestamps();
    }

    /**
     * Get the health records for this student.
     */
    public function healthRecords(): HasMany
    {
        return $this->hasMany(StudentHealthRecord::class, 'user_id');
    }

    /**
     * Get the external hours loaded for this student.
     */
    public function externalHours(): HasMany
    {
        return $this->hasMany(ExternalHour::class, 'user_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumb')
                    ->width(150)
                    ->height(150)
                    ->fit(Fit::Crop, 150, 150);
            });
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('avatar', 'thumb')
            ?: $this->getFirstMediaUrl('avatar');
    }
}
