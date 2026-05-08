<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateRoleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index(): Response
    {
        Gate::authorize('roles.view');

        return Inertia::render('admin/roles/index', [
            'roles' => Role::with('permissions')->get(),
        ]);
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role): Response
    {
        Gate::authorize('roles.view');

        $users = $role->users()
            ->select('id', 'name', 'cedula', 'email')
            ->with('roles')
            ->paginate(5);

        return Inertia::render('admin/roles/show', [
            'role' => $role->load('permissions'),
            'users' => $users,
            'filters' => [
                'search' => null,
                'per_page' => 5,
            ],
        ]);
    }

    /**
     * Get paginated/filtered users for this role.
     */
    public function users(Role $role): Response
    {
        Gate::authorize('roles.view');

        $perPage = min((int) request('per_page', 5), 100);

        $users = $role->users()
            ->when(request('search'), fn ($q, $search) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('cedula', 'like', "%{$search}%");
            }))
            ->select('id', 'name', 'cedula', 'email')
            ->with('roles')
            ->paginate($perPage);

        return Inertia::render('admin/roles/show', [
            'role' => $role->load('permissions'),
            'users' => $users,
            'filters' => [
                'search' => request('search'),
                'per_page' => $perPage,
            ],
        ]);
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Role $role): Response
    {
        Gate::authorize('roles.edit');

        return Inertia::render('admin/roles/edit', [
            'role' => $role->load('permissions'),
            'allPermissions' => Permission::orderBy('name')->get(),
        ]);
    }

    /**
     * Update the specified role in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        Gate::authorize('roles.edit');

        $role->syncPermissions($request->validated('permissions') ?? []);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Permisos del rol actualizados correctamente.');
    }
}
