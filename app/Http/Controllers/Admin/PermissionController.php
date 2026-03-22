<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
}
