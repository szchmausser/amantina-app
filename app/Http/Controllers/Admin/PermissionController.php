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

        return Inertia::render('admin/permissions/show', [
            'permission' => $permission,
            'users' => User::permission($permission->name)
                ->select('id', 'name', 'cedula', 'email')
                ->limit(100)
                ->get(),
        ]);
    }
}
