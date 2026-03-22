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

        return Inertia::render('admin/roles/show', [
            'role' => $role->load('permissions'),
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
        $role->syncPermissions($request->validated('permissions') ?? []);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Permisos del rol actualizados correctamente.');
    }
}
