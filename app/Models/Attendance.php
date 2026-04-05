<?php

namespace App\Models;

use Askedio\SoftCascade\Traits\SoftCascadeTrait;
use Database\Factories\AttendanceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attendance extends Model
{
    /** @use HasFactory<AttendanceFactory> */
    use HasFactory, SoftCascadeTrait, SoftDeletes;

    protected $softCascade = ['attendanceActivities'];

    protected $fillable = [
        'field_session_id',
        'user_id',
        'academic_year_id',
        'attended',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'attended' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<FieldSession, $this>
     */
    public function fieldSession(): BelongsTo
    {
        return $this->belongsTo(FieldSession::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<AcademicYear, $this>
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * @return HasMany<AttendanceActivity, $this>
     */
    public function attendanceActivities(): HasMany
    {
        return $this->hasMany(AttendanceActivity::class);
    }
}
