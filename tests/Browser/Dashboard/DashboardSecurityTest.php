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
// TESTS: Admin puede ver su dashboard
// ============================================================================

test('admin can view admin dashboard', function () {
    $this->actingAs($this->admin);

    $response = $this->get('/dashboard');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('admin/dashboard'));
});

test('admin dashboard shows institution-wide data', function () {
    $this->actingAs($this->admin);

    $response = $this->get('/dashboard');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('admin/dashboard')
        ->has('activeYear')
        ->has('globalCompliance')
        ->has('sectionRanking')
    );
});

// ============================================================================
// TESTS: Profesor puede ver su dashboard
// ============================================================================

test('profesor can view teacher dashboard', function () {
    $this->actingAs($this->profesor);

    $response = $this->get('/dashboard');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('teacher/dashboard'));
});

test('profesor dashboard shows section-specific data', function () {
    $this->actingAs($this->profesor);

    $response = $this->get('/dashboard');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('teacher/dashboard')
        ->has('activeYear')
        ->has('sections')
        ->has('ownSessions')
    );
});

// ============================================================================
// TESTS: Alumno puede ver su dashboard
// ============================================================================

test('alumno can view student dashboard', function () {
    $this->actingAs($this->alumno);

    $response = $this->get('/dashboard');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('student/dashboard'));
});

test('alumno dashboard shows personal progress data', function () {
    $this->actingAs($this->alumno);

    $response = $this->get('/dashboard');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('student/dashboard')
        ->has('activeYear')
        ->has('progress')
        ->has('sessionHistory')
    );
});

// ============================================================================
// TESTS: Representante puede ver dashboard de representados
// ============================================================================

test('representante can view representative dashboard', function () {
    $this->actingAs($this->representante);

    $response = $this->get('/dashboard');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('representative/dashboard'));
});

test('representante dashboard shows student progress data', function () {
    $this->actingAs($this->representante);

    $response = $this->get('/dashboard');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('representative/dashboard')
        ->has('activeYear')
        ->has('progress')
    );
});

// ============================================================================
// TESTS: Usuarios no autenticados no pueden acceder
// ============================================================================

test('unauthenticated user cannot access dashboard', function () {
    $response = $this->get('/dashboard');

    $response->assertStatus(302);
    $response->assertRedirect('/login');
});

// ============================================================================
// TESTS: Dashboard respeta el año académico seleccionado
// ============================================================================

test('admin can view dashboard for specific academic year', function () {
    $anotherYear = AcademicYear::factory()->create([
        'name' => '2025-2026',
        'is_active' => false,
    ]);

    $this->actingAs($this->admin);

    $response = $this->get("/dashboard?year={$anotherYear->id}");

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('admin/dashboard')
        ->where('activeYear.id', $anotherYear->id)
        ->where('activeYear.name', '2025-2026')
    );
});

test('profesor can view dashboard for specific academic year', function () {
    $anotherYear = AcademicYear::factory()->create([
        'name' => '2025-2026',
        'is_active' => false,
    ]);

    $this->actingAs($this->profesor);

    $response = $this->get("/dashboard?year={$anotherYear->id}");

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('teacher/dashboard')
        ->where('activeYear.id', $anotherYear->id)
    );
});

test('alumno can view dashboard for specific academic year', function () {
    $anotherYear = AcademicYear::factory()->create([
        'name' => '2025-2026',
        'is_active' => false,
    ]);

    $this->actingAs($this->alumno);

    $response = $this->get("/dashboard?year={$anotherYear->id}");

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('student/dashboard')
        ->where('activeYear.id', $anotherYear->id)
    );
});

test('representante can view dashboard for specific academic year', function () {
    $anotherYear = AcademicYear::factory()->create([
        'name' => '2025-2026',
        'is_active' => false,
    ]);

    $this->actingAs($this->representante);

    $response = $this->get("/dashboard?year={$anotherYear->id}");

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('representative/dashboard')
        ->where('activeYear.id', $anotherYear->id)
    );
});
