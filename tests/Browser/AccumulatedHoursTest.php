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

    $this->otroAlumno = User::factory()->create(['name' => 'Otro Alumno User']);
    $this->otroAlumno->assignRole('alumno');

    $this->representante = User::factory()->create(['name' => 'Representante User']);
    $this->representante->assignRole('representante');

    // Crear año académico
    $this->academicYear = AcademicYear::factory()->create([
        'name' => '2024-2025',
        'is_active' => true,
        'start_date' => '2024-09-01',
        'end_date' => '2025-07-31',
        'required_hours' => 600.00,
    ]);
});

// ============================================================================
// TESTS: Verificar que los usuarios tienen el permiso accumulated_hours.view
// ============================================================================

test('admin has accumulated_hours.view permission', function () {
    expect($this->admin->hasPermissionTo('accumulated_hours.view'))->toBeTrue();
});

test('profesor has accumulated_hours.view permission', function () {
    expect($this->profesor->hasPermissionTo('accumulated_hours.view'))->toBeTrue();
});

test('alumno has accumulated_hours.view permission', function () {
    expect($this->alumno->hasPermissionTo('accumulated_hours.view'))->toBeTrue();
});

test('representante has accumulated_hours.view permission', function () {
    expect($this->representante->hasPermissionTo('accumulated_hours.view'))->toBeTrue();
});

// ============================================================================
// TESTS: Admin puede ver horas acumuladas de todos
// ============================================================================

test('admin can view accumulated hours in dashboard', function () {
    $this->actingAs($this->admin);

    $response = $this->get('/dashboard');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('admin/dashboard')
        ->has('globalCompliance')
        ->has('sectionRanking')
    );
});

test('admin can view user profile with accumulated hours', function () {
    $this->actingAs($this->admin);

    $response = $this->get("/admin/users/{$this->alumno->id}");

    $response->assertStatus(200);
    // El admin puede ver el perfil de cualquier usuario
});

// ============================================================================
// TESTS: Profesor puede ver horas de sus estudiantes
// ============================================================================

test('profesor can view accumulated hours in dashboard', function () {
    $this->actingAs($this->profesor);

    $response = $this->get('/dashboard');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('teacher/dashboard')
        ->has('sections')
    );
});

// ============================================================================
// TESTS: Alumno puede ver sus propias horas
// ============================================================================

test('alumno can view own accumulated hours in dashboard', function () {
    $this->actingAs($this->alumno);

    $response = $this->get('/dashboard');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('student/dashboard')
        ->has('progress')
    );
});

test('alumno can view own user profile', function () {
    $this->actingAs($this->alumno);

    $response = $this->get("/admin/users/{$this->alumno->id}");

    // El alumno puede ver su propio perfil
    $response->assertStatus(200);
});

test('alumno cannot view other student profile', function () {
    $this->actingAs($this->alumno);

    $response = $this->get("/admin/users/{$this->otroAlumno->id}");

    // El alumno NO puede ver el perfil de otro alumno
    $response->assertForbidden();
});

// ============================================================================
// TESTS: Representante puede ver horas de representados
// ============================================================================

test('representante can view accumulated hours in dashboard', function () {
    $this->actingAs($this->representante);

    $response = $this->get('/dashboard');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('representative/dashboard')
        ->has('progress')
    );
});

// ============================================================================
// TESTS: Usuarios sin autenticar no pueden ver horas acumuladas
// ============================================================================

test('unauthenticated user cannot view accumulated hours', function () {
    $response = $this->get('/dashboard');

    $response->assertStatus(302);
    $response->assertRedirect('/login');
});

test('unauthenticated user cannot view user profiles', function () {
    $response = $this->get("/admin/users/{$this->alumno->id}");

    $response->assertStatus(302);
    $response->assertRedirect('/login');
});
