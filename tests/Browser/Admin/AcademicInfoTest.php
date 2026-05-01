<?php

use App\Models\AcademicYear;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    // Crear usuarios con roles
    $this->admin = User::factory()->create(['name' => 'Admin User']);
    $this->admin->assignRole('admin');

    $this->profesor = User::factory()->create(['name' => 'Profesor User']);
    $this->profesor->assignRole('profesor');

    $this->alumno = User::factory()->create(['name' => 'Alumno User']);
    $this->alumno->assignRole('alumno');

    $this->representante = User::factory()->create(['name' => 'Representante User']);
    $this->representante->assignRole('representante');

    // Crear año académico activo
    $this->academicYear = AcademicYear::factory()->create([
        'name' => '2024-2025',
        'is_active' => true,
        'start_date' => '2024-09-01',
        'end_date' => '2025-07-31',
        'required_hours' => 600.00,
    ]);
});

// ============================================================================
// TESTS: Verificar permisos academic_info.view
// ============================================================================

test('admin has academic_info.view permission', function () {
    expect($this->admin->hasPermissionTo('academic_info.view'))->toBeTrue();
});

test('profesor has academic_info.view permission', function () {
    expect($this->profesor->hasPermissionTo('academic_info.view'))->toBeTrue();
});

test('alumno does not have academic_info.view permission', function () {
    expect($this->alumno->hasPermissionTo('academic_info.view'))->toBeFalse();
});

test('representante does not have academic_info.view permission', function () {
    expect($this->representante->hasPermissionTo('academic_info.view'))->toBeFalse();
});

// ============================================================================
// TESTS: Admin puede ver información académica
// ============================================================================

test('admin can view academic info page', function () {
    $this->actingAs($this->admin);

    $response = $this->get('/admin/academic-info');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('admin/academic-info/index')
        ->has('activeYear')
        ->has('currentTerm')
        ->has('grades')
    );
});

// ============================================================================
// TESTS: Profesor puede ver información académica
// ============================================================================

test('profesor can view academic info page', function () {
    $this->actingAs($this->profesor);

    $response = $this->get('/admin/academic-info');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('admin/academic-info/index')
        ->has('activeYear')
        ->has('currentTerm')
        ->has('grades')
    );
});

// ============================================================================
// TESTS: Alumno NO puede ver información académica
// ============================================================================

test('alumno cannot view academic info page', function () {
    $this->actingAs($this->alumno);

    $response = $this->get('/admin/academic-info');

    $response->assertForbidden();
});

// ============================================================================
// TESTS: Representante NO puede ver información académica
// ============================================================================

test('representante cannot view academic info page', function () {
    $this->actingAs($this->representante);

    $response = $this->get('/admin/academic-info');

    $response->assertForbidden();
});

// ============================================================================
// TESTS: Usuarios sin autenticar no pueden ver información académica
// ============================================================================

test('unauthenticated user cannot view academic info page', function () {
    $response = $this->get('/admin/academic-info');

    $response->assertStatus(302);
    $response->assertRedirect('/login');
});

// ============================================================================
// TESTS: Validación de datos cuando no hay año académico activo
// ============================================================================

test('admin can view academic info page when no active year exists', function () {
    // Desactivar el año académico
    $this->academicYear->update(['is_active' => false]);

    $this->actingAs($this->admin);

    $response = $this->get('/admin/academic-info');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('admin/academic-info/index')
        ->where('activeYear', null)
        ->where('currentTerm', null)
        ->where('grades', [])
    );
});

test('profesor can view academic info page when no active year exists', function () {
    // Desactivar el año académico
    $this->academicYear->update(['is_active' => false]);

    $this->actingAs($this->profesor);

    $response = $this->get('/admin/academic-info');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('admin/academic-info/index')
        ->where('activeYear', null)
        ->where('currentTerm', null)
        ->where('grades', [])
    );
});
