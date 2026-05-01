<?php

use App\Models\ActivityCategory;
use App\Models\HealthCondition;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->admin = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
    ]);
    $this->admin->assignRole('admin');

    $this->actingAs($this->admin);
});

// ─── CATEGORÍAS DE ACTIVIDADES ────────────────────────────────────────────────

test('admin puede ver el listado de categorías de actividades', function () {
    $page = visit('/admin/activity-categories');

    $page->assertPathIs('/admin/activity-categories')
        ->assertNoJavaScriptErrors();
});

test('admin puede ver categorías existentes', function () {
    ActivityCategory::factory()->create(['name' => 'Siembra']);
    ActivityCategory::factory()->create(['name' => 'Cosecha']);

    $page = visit('/admin/activity-categories');

    $page->assertSee('Siembra')
        ->assertSee('Cosecha')
        ->assertNoJavaScriptErrors();
});

test('profesor puede ver categorías de actividades', function () {
    $profesor = User::factory()->create();
    $profesor->assignRole('profesor');

    $this->actingAs($profesor);

    $page = visit('/admin/activity-categories');

    $page->assertPathIs('/admin/activity-categories')
        ->assertNoJavaScriptErrors();
});

// ============================================================================
// TESTS DE SEGURIDAD COMPLETOS PARA ACTIVITY CATEGORIES (activity_categories.*)
// ============================================================================

test('usuario sin permiso activity_categories.view NO puede ver listado (alumno)', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno'); // alumno NO tiene activity_categories.view

    $this->actingAs($alumno);

    $page = visit('/admin/activity-categories');

    $page->assertSee('403');
});

test('usuario sin permiso activity_categories.view NO puede ver listado (representante)', function () {
    $representante = User::factory()->create();
    $representante->assignRole('representante'); // representante NO tiene activity_categories.view

    $this->actingAs($representante);

    $page = visit('/admin/activity-categories');

    $page->assertSee('403');
});

test('usuario CON permiso activity_categories.create SÍ puede crear mediante POST (admin)', function () {
    $this->actingAs($this->admin); // admin SÍ tiene activity_categories.create

    $response = $this->post('/admin/activity-categories', [
        'name' => 'Nueva Categoría Admin',
        'description' => 'Descripción de prueba',
    ]);

    $response->assertStatus(302); // Redirección exitosa

    // Verificar que SÍ se creó
    $this->assertDatabaseHas('activity_categories', [
        'name' => 'Nueva Categoría Admin',
    ]);
});

test('usuario CON permiso activity_categories.create SÍ puede crear mediante POST (profesor)', function () {
    $profesor = User::factory()->create();
    $profesor->assignRole('profesor'); // profesor SÍ tiene activity_categories.create

    $this->actingAs($profesor);

    $response = $this->post('/admin/activity-categories', [
        'name' => 'Nueva Categoría Profesor',
        'description' => 'Descripción de prueba',
    ]);

    $response->assertStatus(302); // Redirección exitosa

    // Verificar que SÍ se creó
    $this->assertDatabaseHas('activity_categories', [
        'name' => 'Nueva Categoría Profesor',
    ]);
});

test('usuario sin permiso activity_categories.create NO puede crear mediante POST (alumno)', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno'); // alumno NO tiene activity_categories.create

    $this->actingAs($alumno);

    $response = $this->post('/admin/activity-categories', [
        'name' => 'Categoría Maliciosa Alumno',
        'description' => 'Intento de creación',
    ]);

    $response->assertStatus(403);

    // Verificar que NO se creó
    $this->assertDatabaseMissing('activity_categories', [
        'name' => 'Categoría Maliciosa Alumno',
    ]);
});

test('usuario sin permiso activity_categories.create NO puede crear mediante POST (representante)', function () {
    $representante = User::factory()->create();
    $representante->assignRole('representante'); // representante NO tiene activity_categories.create

    $this->actingAs($representante);

    $response = $this->post('/admin/activity-categories', [
        'name' => 'Categoría Maliciosa Representante',
        'description' => 'Intento de creación',
    ]);

    $response->assertStatus(403);

    // Verificar que NO se creó
    $this->assertDatabaseMissing('activity_categories', [
        'name' => 'Categoría Maliciosa Representante',
    ]);
});

test('usuario CON permiso activity_categories.edit SÍ puede editar mediante PUT (admin)', function () {
    $category = ActivityCategory::factory()->create(['name' => 'Categoría Original Admin']);

    $this->actingAs($this->admin); // admin SÍ tiene activity_categories.edit

    $response = $this->put("/admin/activity-categories/{$category->id}", [
        'name' => 'Categoría Editada Admin',
        'description' => 'Descripción editada',
    ]);

    $response->assertStatus(302); // Redirección exitosa

    // Verificar que SÍ se modificó
    $this->assertDatabaseHas('activity_categories', [
        'id' => $category->id,
        'name' => 'Categoría Editada Admin',
    ]);
});

test('usuario CON permiso activity_categories.edit SÍ puede editar mediante PUT (profesor)', function () {
    $category = ActivityCategory::factory()->create(['name' => 'Categoría Original Profesor']);

    $profesor = User::factory()->create();
    $profesor->assignRole('profesor'); // profesor SÍ tiene activity_categories.edit

    $this->actingAs($profesor);

    $response = $this->put("/admin/activity-categories/{$category->id}", [
        'name' => 'Categoría Editada Profesor',
        'description' => 'Descripción editada',
    ]);

    $response->assertStatus(302); // Redirección exitosa

    // Verificar que SÍ se modificó
    $this->assertDatabaseHas('activity_categories', [
        'id' => $category->id,
        'name' => 'Categoría Editada Profesor',
    ]);
});

test('usuario sin permiso activity_categories.edit NO puede editar mediante PUT (alumno)', function () {
    $category = ActivityCategory::factory()->create(['name' => 'Categoría Protegida']);

    $alumno = User::factory()->create();
    $alumno->assignRole('alumno'); // alumno NO tiene activity_categories.edit

    $this->actingAs($alumno);

    $response = $this->put("/admin/activity-categories/{$category->id}", [
        'name' => 'Categoría Hackeada Alumno',
        'description' => 'Intento de edición',
    ]);

    $response->assertStatus(403);

    // Verificar que NO se modificó
    $this->assertDatabaseHas('activity_categories', [
        'id' => $category->id,
        'name' => 'Categoría Protegida',
    ]);
});

test('usuario sin permiso activity_categories.edit NO puede editar mediante PUT (representante)', function () {
    $category = ActivityCategory::factory()->create(['name' => 'Categoría Protegida 2']);

    $representante = User::factory()->create();
    $representante->assignRole('representante'); // representante NO tiene activity_categories.edit

    $this->actingAs($representante);

    $response = $this->put("/admin/activity-categories/{$category->id}", [
        'name' => 'Categoría Hackeada Representante',
        'description' => 'Intento de edición',
    ]);

    $response->assertStatus(403);

    // Verificar que NO se modificó
    $this->assertDatabaseHas('activity_categories', [
        'id' => $category->id,
        'name' => 'Categoría Protegida 2',
    ]);
});

test('usuario CON permiso activity_categories.delete SÍ puede eliminar mediante DELETE (admin)', function () {
    $category = ActivityCategory::factory()->create(['name' => 'Categoría Eliminable Admin']);

    $this->actingAs($this->admin); // admin SÍ tiene activity_categories.delete

    $response = $this->delete("/admin/activity-categories/{$category->id}");

    $response->assertStatus(302); // Redirección exitosa

    // Verificar que SÍ se eliminó (soft delete)
    $this->assertSoftDeleted('activity_categories', [
        'id' => $category->id,
    ]);
});

test('usuario CON permiso activity_categories.delete SÍ puede eliminar mediante DELETE (profesor)', function () {
    $category = ActivityCategory::factory()->create(['name' => 'Categoría Eliminable Profesor']);

    $profesor = User::factory()->create();
    $profesor->assignRole('profesor'); // profesor SÍ tiene activity_categories.delete

    $this->actingAs($profesor);

    $response = $this->delete("/admin/activity-categories/{$category->id}");

    $response->assertStatus(302); // Redirección exitosa

    // Verificar que SÍ se eliminó (soft delete)
    $this->assertSoftDeleted('activity_categories', [
        'id' => $category->id,
    ]);
});

test('usuario sin permiso activity_categories.delete NO puede eliminar mediante DELETE (alumno)', function () {
    $category = ActivityCategory::factory()->create(['name' => 'Categoría Protegida Delete']);

    $alumno = User::factory()->create();
    $alumno->assignRole('alumno'); // alumno NO tiene activity_categories.delete

    $this->actingAs($alumno);

    $response = $this->delete("/admin/activity-categories/{$category->id}");

    $response->assertStatus(403);

    // Verificar que NO se eliminó
    $this->assertDatabaseHas('activity_categories', [
        'id' => $category->id,
        'name' => 'Categoría Protegida Delete',
    ]);
});

test('usuario sin permiso activity_categories.delete NO puede eliminar mediante DELETE (representante)', function () {
    $category = ActivityCategory::factory()->create(['name' => 'Categoría Protegida Delete 2']);

    $representante = User::factory()->create();
    $representante->assignRole('representante'); // representante NO tiene activity_categories.delete

    $this->actingAs($representante);

    $response = $this->delete("/admin/activity-categories/{$category->id}");

    $response->assertStatus(403);

    // Verificar que NO se eliminó
    $this->assertDatabaseHas('activity_categories', [
        'id' => $category->id,
        'name' => 'Categoría Protegida Delete 2',
    ]);
});

// ─── CONDICIONES DE SALUD ─────────────────────────────────────────────────────

test('admin puede ver el listado de condiciones de salud', function () {
    $page = visit('/admin/health-conditions');

    $page->assertPathIs('/admin/health-conditions')
        ->assertNoJavaScriptErrors();
});

test('admin puede ver condiciones de salud existentes', function () {
    HealthCondition::factory()->create(['name' => 'Asma']);
    HealthCondition::factory()->create(['name' => 'Diabetes']);

    $page = visit('/admin/health-conditions');

    $page->assertSee('Asma')
        ->assertSee('Diabetes')
        ->assertNoJavaScriptErrors();
});

// ─── ROLES Y PERMISOS ─────────────────────────────────────────────────────────

test('admin puede ver el listado de roles', function () {
    $page = visit('/admin/roles');

    $page->assertPathIs('/admin/roles')
        ->assertSee('Roles')
        ->assertNoJavaScriptErrors();
});

test('admin puede ver el listado de permisos', function () {
    $page = visit('/admin/permissions');

    $page->assertPathIs('/admin/permissions')
        ->assertNoJavaScriptErrors();
});

test('alumno no puede acceder a gestión de roles', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');

    $this->actingAs($alumno);

    $page = visit('/admin/roles');

    $page->assertSee('403');
});

// ============================================================================
// TESTS DE SEGURIDAD COMPLETOS PARA ROLES (roles.*)
// ============================================================================

test('usuario sin permiso roles.view NO puede ver listado (profesor)', function () {
    $profesor = User::factory()->create();
    $profesor->assignRole('profesor'); // profesor NO tiene roles.view

    $this->actingAs($profesor);

    $page = visit('/admin/roles');

    $page->assertSee('403');
});

test('usuario sin permiso roles.view NO puede ver listado (representante)', function () {
    $representante = User::factory()->create();
    $representante->assignRole('representante'); // representante NO tiene roles.view

    $this->actingAs($representante);

    $page = visit('/admin/roles');

    $page->assertSee('403');
});

// ============================================================================
// TESTS DE SEGURIDAD COMPLETOS PARA ROLES (roles.*)
// ============================================================================
// NOTA: Los roles se gestionan ÚNICAMENTE a través del seeder.
// Solo se pueden EDITAR los permisos de roles existentes, NO crear/eliminar roles.
// Rutas disponibles: GET index, GET show, GET edit, PUT update (solo permisos)
// ============================================================================

test('usuario CON permiso roles.view SÍ puede ver listado de roles (admin)', function () {
    $this->actingAs($this->admin); // admin SÍ tiene roles.view

    $page = visit('/admin/roles');

    $page->assertPathIs('/admin/roles')
        ->assertSee('admin')
        ->assertSee('profesor')
        ->assertNoJavaScriptErrors();
});

test('usuario sin permiso roles.view NO puede ver listado de roles (profesor)', function () {
    $profesor = User::factory()->create();
    $profesor->assignRole('profesor'); // profesor NO tiene roles.view

    $this->actingAs($profesor);

    $page = visit('/admin/roles');

    $page->assertSee('403');
});

test('usuario sin permiso roles.view NO puede ver listado de roles (alumno)', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno'); // alumno NO tiene roles.view

    $this->actingAs($alumno);

    $page = visit('/admin/roles');

    $page->assertSee('403');
});

test('usuario CON permiso roles.view SÍ puede ver detalle de rol (admin)', function () {
    $role = Role::where('name', 'alumno')->first();

    $this->actingAs($this->admin); // admin SÍ tiene roles.view

    $page = visit("/admin/roles/{$role->id}");

    $page->assertPathIs("/admin/roles/{$role->id}")
        ->assertSee('alumno')
        ->assertNoJavaScriptErrors();
});

test('usuario sin permiso roles.view NO puede ver detalle de rol (profesor)', function () {
    $role = Role::where('name', 'alumno')->first();

    $profesor = User::factory()->create();
    $profesor->assignRole('profesor'); // profesor NO tiene roles.view

    $this->actingAs($profesor);

    $page = visit("/admin/roles/{$role->id}");

    $page->assertSee('403');
});

test('usuario CON permiso roles.edit SÍ puede acceder a formulario de edición (admin)', function () {
    $role = Role::where('name', 'alumno')->first();

    $this->actingAs($this->admin); // admin SÍ tiene roles.edit

    $page = visit("/admin/roles/{$role->id}/edit");

    $page->assertPathIs("/admin/roles/{$role->id}/edit")
        ->assertNoJavaScriptErrors();
});

test('usuario sin permiso roles.edit NO puede acceder a formulario de edición (profesor)', function () {
    $role = Role::where('name', 'alumno')->first();

    $profesor = User::factory()->create();
    $profesor->assignRole('profesor'); // profesor NO tiene roles.edit

    $this->actingAs($profesor);

    $page = visit("/admin/roles/{$role->id}/edit");

    $page->assertSee('403');
});

test('usuario CON permiso roles.edit SÍ puede actualizar permisos de rol mediante PUT (admin)', function () {
    $role = Role::where('name', 'alumno')->first();
    $permisosOriginales = $role->permissions->pluck('name')->toArray();

    $this->actingAs($this->admin); // admin SÍ tiene roles.edit

    // Agregar un permiso nuevo temporalmente
    $response = $this->put("/admin/roles/{$role->id}", [
        'permissions' => array_merge($permisosOriginales, ['dashboard.view']),
    ]);

    $response->assertStatus(302); // Redirección exitosa

    // Verificar que se actualizaron los permisos
    $role->refresh();
    expect($role->hasPermissionTo('dashboard.view'))->toBeTrue();

    // Restaurar permisos originales
    $role->syncPermissions($permisosOriginales);
});

test('usuario sin permiso roles.edit NO puede actualizar permisos de rol mediante PUT (profesor)', function () {
    $role = Role::where('name', 'alumno')->first();
    $permisosOriginales = $role->permissions->pluck('name')->toArray();

    $profesor = User::factory()->create();
    $profesor->assignRole('profesor'); // profesor NO tiene roles.edit

    $this->actingAs($profesor);

    $response = $this->put("/admin/roles/{$role->id}", [
        'permissions' => ['users.view', 'users.create', 'users.edit', 'users.delete'], // Intentando dar permisos peligrosos
    ]);

    $response->assertStatus(403);

    // Verificar que NO se modificaron los permisos
    $role->refresh();
    expect($role->permissions->pluck('name')->toArray())->toBe($permisosOriginales);
});

test('usuario sin permiso roles.edit NO puede actualizar permisos de rol mediante PUT (alumno)', function () {
    $role = Role::where('name', 'profesor')->first();
    $permisosOriginales = $role->permissions->pluck('name')->toArray();

    $alumno = User::factory()->create();
    $alumno->assignRole('alumno'); // alumno NO tiene roles.edit

    $this->actingAs($alumno);

    $response = $this->put("/admin/roles/{$role->id}", [
        'permissions' => ['users.view', 'users.create', 'users.edit', 'users.delete'], // Intentando dar permisos peligrosos
    ]);

    $response->assertStatus(403);

    // Verificar que NO se modificaron los permisos
    $role->refresh();
    expect($role->permissions->pluck('name')->toArray())->toBe($permisosOriginales);
});

// ============================================================================
// TESTS DE SEGURIDAD COMPLETOS PARA PERMISSIONS (permissions.*)
// ============================================================================
// NOTA: El sistema actual solo permite VER permissions (index)
// NO permite crear/editar/eliminar permissions dinámicamente

test('usuario CON permiso permissions.view SÍ puede ver listado (admin)', function () {
    $this->actingAs($this->admin); // admin SÍ tiene permissions.view

    $page = visit('/admin/permissions');

    $page->assertPathIs('/admin/permissions')
        ->assertSee('Permisos')
        ->assertNoJavaScriptErrors();
});

test('usuario sin permiso permissions.view NO puede ver listado (profesor)', function () {
    $profesor = User::factory()->create();
    $profesor->assignRole('profesor'); // profesor NO tiene permissions.view

    $this->actingAs($profesor);

    $page = visit('/admin/permissions');

    $page->assertSee('403');
});

test('usuario sin permiso permissions.view NO puede ver listado (alumno)', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno'); // alumno NO tiene permissions.view

    $this->actingAs($alumno);

    $page = visit('/admin/permissions');

    $page->assertSee('403');
});

test('usuario sin permiso permissions.view NO puede ver listado (representante)', function () {
    $representante = User::factory()->create();
    $representante->assignRole('representante'); // representante NO tiene permissions.view

    $this->actingAs($representante);

    $page = visit('/admin/permissions');

    $page->assertSee('403');
});
