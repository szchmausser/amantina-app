<?php

namespace Tests\Browser\HappyPath;

use App\Models\ActivityCategory;
use App\Models\HealthCondition;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Pest\Browser\Browsable;
use Spatie\Permission\Models\Role;

uses(DatabaseTruncation::class);
uses(Browsable::class);

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
        ->assertSee('Categorías de Actividades')
        ->assertSee('Nueva Categoría')
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

// ─── CONDICIONES DE SALUD ─────────────────────────────────────────────────────

test('admin puede ver el listado de condiciones de salud', function () {
    $page = visit('/admin/health-conditions');

    $page->assertPathIs('/admin/health-conditions')
        ->assertSee('Condiciones de Salud')
        ->assertSee('Nueva Condición')
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
        ->assertSee('Roles del Sistema')
        ->assertNoJavaScriptErrors();
});

test('admin puede ver el listado de permisos', function () {
    $page = visit('/admin/permissions');

    $page->assertPathIs('/admin/permissions')
        ->assertSee('Permisos del Sistema')
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

    $page->assertPathIs("/admin/roles/{$role->id}/users")
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
