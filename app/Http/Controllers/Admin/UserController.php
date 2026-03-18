<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        $query = User::query()->with('roles');

        if ($request->filled('search')) {
            $search = $request->string('search')->lower()->toString();

            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(cedula) LIKE ?', ["%{$search}%"]);
            });
        }

        if ($request->filled('role')) {
            $query->role($request->input('role'));
        }

        $users = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role']),
            'availableRoles' => Role::all()->pluck('name'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        Gate::authorize('create', User::class);

        return Inertia::render('admin/users/create', [
            'roles' => Role::all()->pluck('name'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $isAlumno = $request->role === 'alumno';

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'cedula' => $request->cedula,
            'phone' => $request->phone,
            'address' => $request->address,
            'password' => Hash::make($request->password),
            'is_transfer' => $isAlumno ? ($request->is_transfer ?? false) : false,
            'institution_origin' => $isAlumno ? $request->institution_origin : null,
            'is_active' => true,
        ]);

        $user->assignRole($request->role);

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): Response
    {
        Gate::authorize('update', $user);

        return Inertia::render('admin/users/edit', [
            'user' => $user->load('roles'),
            'roles' => Role::all()->pluck('name'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $isAlumno = $request->role === 'alumno';

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'cedula' => $request->cedula,
            'phone' => $request->phone,
            'address' => $request->address,
            'is_transfer' => $isAlumno ? ($request->is_transfer ?? false) : false,
            'institution_origin' => $isAlumno ? $request->institution_origin : null,
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        $user->syncRoles([$request->role]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }
}
