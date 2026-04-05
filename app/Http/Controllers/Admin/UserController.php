<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\HealthCondition;
use App\Models\RelationshipType;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
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

        $perPage = $request->integer('per_page', 5);
        // Validar que per_page esté en los valores permitidos
        if (! in_array($perPage, [5, 15, 25, 50, 100])) {
            $perPage = 5;
        }

        $users = $query->latest()->paginate($perPage)->withQueryString();

        return Inertia::render('admin/users/index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role', 'per_page']),
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
        Gate::authorize('create', User::class);

        $roles = $request->input('roles', []);
        $isAlumno = in_array('alumno', (array) $roles);

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

        $user->syncRoles($roles);

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): Response
    {
        Gate::authorize('view', $user);

        $representativeRole = Role::where('name', 'representante')->first();

        $user->load([
            'roles.permissions',
            'permissions',
            'representedStudents',
            'healthRecords.condition',
            'healthRecords.receivedBy',
            'healthRecords.media',
        ]);

        $representatives = \DB::table('student_representatives')
            ->join('users as reps', 'reps.id', '=', 'student_representatives.representative_id')
            ->leftJoin('relationship_types', 'relationship_types.id', '=', 'student_representatives.relationship_type_id')
            ->where('student_representatives.student_id', $user->id)
            ->whereNull('student_representatives.deleted_at')
            ->select([
                'reps.id',
                'reps.name',
                'reps.cedula',
                'reps.phone',
                'student_representatives.id as pivot_id',
                'student_representatives.relationship_type_id',
                'relationship_types.name as pivot_relationship_type_name',
            ])
            ->get()
            ->map(function ($rep) {
                return (object) [
                    'id' => $rep->id,
                    'name' => $rep->name,
                    'cedula' => $rep->cedula,
                    'phone' => $rep->phone,
                    'pivot' => (object) [
                        'id' => $rep->pivot_id,
                        'relationship_type_id' => $rep->relationship_type_id,
                        'relationship_type_name' => $rep->pivot_relationship_type_name,
                    ],
                ];
            });

        $user->setRelation('representatives', $representatives);

        return Inertia::render('admin/users/show', [
            'user' => $user,
            'relationshipTypes' => RelationshipType::where('is_active', true)->get(),
            'availableRepresentatives' => $representativeRole
                ? User::role('representante')->get(['id', 'name', 'cedula'])
                : [],
            'healthConditions' => HealthCondition::where('is_active', true)->get(),
            'hourHistory' => $user->hasRole('alumno')
                ? Attendance::where('user_id', $user->id)
                    ->with(['fieldSession' => function ($query) {
                        $query->with('status');
                    }, 'attendanceActivities.activityCategory'])
                    ->orderBy('created_at', 'desc')
                    ->limit(50)
                    ->get()
                    ->map(fn ($a) => [
                        'id' => $a->id,
                        'attended' => $a->attended,
                        'notes' => $a->notes,
                        'created_at' => $a->created_at->format('d/m/Y H:i'),
                        'fieldSession' => $a->fieldSession ? [
                            'id' => $a->fieldSession->id,
                            'name' => $a->fieldSession->name,
                            'start_datetime' => $a->fieldSession->start_datetime?->format('d/m/Y'),
                            'status' => $a->fieldSession->status?->name,
                        ] : null,
                        'activities' => $a->attendanceActivities->map(fn ($act) => [
                            'id' => $act->id,
                            'hours' => (float) $act->hours,
                            'activity_category' => $act->activityCategory?->name,
                        ])->values(),
                    ])
                : [],
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): Response
    {
        Gate::authorize('update', $user);

        return Inertia::render('admin/users/edit', [
            'user' => $user->load(['roles.permissions', 'permissions']),
            'roles' => Role::all()->pluck('name'),
            'allPermissions' => Permission::orderBy('name')->get()->pluck('name'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        $roles = $request->input('roles', []);
        $isAlumno = in_array('alumno', (array) $roles);

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

        $user->syncRoles($roles);

        if ($request->has('direct_permissions')) {
            $user->syncPermissions($request->validated('direct_permissions') ?? []);
        }

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
