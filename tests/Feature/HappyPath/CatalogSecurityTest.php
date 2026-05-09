<?php

namespace Tests\Feature\HappyPath;

use App\Models\ActivityCategory;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->admin = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
    ]);
    $this->admin->assignRole('admin');

    $this->actingAs($this->admin);
});

// ============================================================================
// TESTS DE SEGURIDAD COMPLETOS PARA ACTIVITY CATEGORIES (activity_categories.*)
// ============================================================================

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

// ============================================================================
// TESTS DE SEGURIDAD COMPLETOS PARA ROLES (roles.*)
// ============================================================================
// NOTA: Los roles se gestionan ÚNICAMENTE a través del seeder.
// Solo se pueden EDITAR los permisos de roles existentes, NO crear/eliminar roles.
// Rutas disponibles: GET index, GET show, GET edit, PUT update (solo permisos)
// ============================================================================

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
