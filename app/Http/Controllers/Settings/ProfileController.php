<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileDeleteRequest;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();
        $user->load([
            'roles.permissions',
            'healthRecords.condition',
            'healthRecords.media',
        ]);

        $roles = $user->roles->pluck('name')->toArray();
        $isAlumno = in_array('alumno', $roles);
        $isRepresentante = in_array('representante', $roles);
        $isAdminOrProfesor = in_array('admin', $roles) || in_array('profesor', $roles);

        // Load representatives with relationship types
        $representatives = $isAlumno
            ? \DB::table('student_representatives')
                ->join('users', 'users.id', '=', 'student_representatives.representative_id')
                ->leftJoin('relationship_types', 'relationship_types.id', '=', 'student_representatives.relationship_type_id')
                ->where('student_representatives.student_id', $user->id)
                ->whereNull('student_representatives.deleted_at')
                ->select('users.id', 'users.name', 'users.cedula', 'users.phone', 'relationship_types.name as relationship_type_name')
                ->get()
                ->map(fn ($r) => (array) $r)
            : [];

        // Load represented students with relationship types
        $representedStudents = $isRepresentante
            ? \DB::table('student_representatives')
                ->join('users', 'users.id', '=', 'student_representatives.student_id')
                ->leftJoin('relationship_types', 'relationship_types.id', '=', 'student_representatives.relationship_type_id')
                ->where('student_representatives.representative_id', $user->id)
                ->whereNull('student_representatives.deleted_at')
                ->select('users.id', 'users.name', 'users.cedula', 'relationship_types.name as relationship_type_name')
                ->get()
                ->map(fn ($r) => (array) $r)
            : [];

        // Group permissions by module
        $rolePermissions = [];
        $user->roles->each(function ($role) use (&$rolePermissions) {
            $role->permissions->each(function ($permission) use (&$rolePermissions) {
                $module = explode('.', $permission->name)[0];
                if (! isset($rolePermissions[$module])) {
                    $rolePermissions[$module] = [];
                }
                $rolePermissions[$module][] = $permission->name;
            });
        });
        // Unique permissions
        foreach ($rolePermissions as $module => $perms) {
            $rolePermissions[$module] = array_unique($perms);
        }

        return Inertia::render('settings/profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
            'avatar_url' => $user->avatar_url,
            'userRoles' => $roles,
            'userPermissions' => $rolePermissions,
            'isAlumno' => $isAlumno,
            'isRepresentante' => $isRepresentante,
            'showRolesAndPermissions' => $isAdminOrProfesor,
            'canDeleteAccount' => in_array('admin', $roles),
            'userData' => [
                'cedula' => $user->cedula,
                'phone' => $user->phone,
                'address' => $user->address,
                'is_transfer' => $user->is_transfer,
                'institution_origin' => $user->institution_origin,
                'is_active' => $user->is_active,
            ],
            'representatives' => $isAlumno ? $user->representatives->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'cedula' => $r->cedula,
                'phone' => $r->phone,
                'relationship_type_name' => $r->pivot->relationshipType?->name ?? 'No especificado',
            ])->values() : [],
            'representedStudents' => $isRepresentante ? $user->representedStudents->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'cedula' => $s->cedula,
                'relationship_type_name' => $s->pivot->relationshipType?->name ?? 'No especificado',
            ])->values() : [],
            'healthRecords' => $isAlumno ? $user->healthRecords->map(fn ($r) => [
                'id' => $r->id,
                'condition' => $r->condition?->name,
                'received_at' => $r->received_at?->format('d/m/Y H:i'),
                'received_at_location' => $r->received_at_location,
                'observations' => $r->observations,
                'media' => $r->media->map(fn ($m) => [
                    'id' => $m->id,
                    'file_name' => $m->file_name,
                    'url' => $m->getUrl(),
                    'description' => $m->getCustomProperty('description', ''),
                ])->values(),
            ])->values() : [],
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return to_route('profile.edit');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(ProfileDeleteRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Only admins can delete their own accounts
        if (! $user->hasRole('admin')) {
            abort(403, 'Solo los administradores pueden eliminar cuentas. Contacte a un administrador.');
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Update the user's avatar.
     */
    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,png,gif,webp', 'max:2048'],
        ]);

        $user = $request->user();

        if ($user->getFirstMedia('avatar')) {
            $user->getFirstMedia('avatar')->delete();
        }

        $user->addMediaFromRequest('avatar')
            ->toMediaCollection('avatar');

        return back()->with('success', 'Avatar actualizado correctamente.');
    }

    /**
     * Remove the user's avatar.
     */
    public function removeAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->getFirstMedia('avatar')) {
            $user->getFirstMedia('avatar')->delete();
        }

        return back()->with('success', 'Avatar eliminado correctamente.');
    }
}
