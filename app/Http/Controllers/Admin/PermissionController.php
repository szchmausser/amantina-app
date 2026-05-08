<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions.
     */
    public function index(): Response
    {
        Gate::authorize('permissions.view');

        return Inertia::render('admin/permissions/index', [
            'permissions' => Permission::with('roles')->orderBy('name')->get(),
        ]);
    }

    /**
     * Display the specified permission.
     */
    public function show(Permission $permission): Response
    {
        Gate::authorize('permissions.view');

        $permission->load('roles');

        $users = User::permission($permission->name)
            ->select('id', 'name', 'cedula', 'email')
            ->with('roles')
            ->paginate(5);

        return Inertia::render('admin/permissions/show', [
            'permission' => $permission,
            'users' => $users,
            'filters' => [
                'search' => null,
                'role' => null,
                'per_page' => 5,
            ],
            'availableRoles' => $permission->roles->pluck('name'),
        ]);
    }

    /**
     * Get paginated/filtered users for this permission.
     */
    public function users(Permission $permission): Response
    {
        Gate::authorize('permissions.view');

        $perPage = min((int) request('per_page', 5), 100);

        $permission->load('roles');

        $users = User::permission($permission->name)
            ->when(request('search'), fn ($q, $search) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('cedula', 'like', "%{$search}%");
            }))
            ->when(
                request('role') && request('role') !== 'all',
                fn ($q) => $q->role(request('role'))
            )
            ->select('id', 'name', 'cedula', 'email')
            ->with('roles')
            ->paginate($perPage);

        return Inertia::render('admin/permissions/show', [
            'permission' => $permission,
            'users' => $users,
            'filters' => [
                'search' => request('search'),
                'role' => request('role'),
                'per_page' => $perPage,
            ],
            'availableRoles' => $permission->roles->pluck('name'),
        ]);
    }
}
