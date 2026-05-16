<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\RelationshipType;
use App\Models\Section;
use App\Models\StudentRepresentative;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Pest\Browser\Browsable;

uses(DatabaseTruncation::class);
uses(Browsable::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->academicYear = AcademicYear::factory()->create([
        'name' => '2024-2025',
        'is_active' => true,
        'required_hours' => 600,
    ]);

    $this->grade = Grade::factory()->for($this->academicYear)->create(['name' => '1er Año']);
    $this->section = Section::factory()->for($this->academicYear)->for($this->grade)->create(['name' => 'A']);
});

test('admin ve el dashboard administrativo', function () {
    $admin = User::factory()->create(['name' => 'Admin Test']);
    $admin->assignRole('admin');

    $this->actingAs($admin);

    $page = visit('/dashboard');

    $page->assertPathIs('/dashboard')
        ->assertSee('Panel de Administración')
        ->assertSee('2024-2025')
        ->assertSee('600h requeridas')
        ->assertSee('Estudiantes Activos: 0')
        ->assertSee('No hay secciones registradas')
        ->assertNoJavaScriptErrors();
});

test('profesor ve el dashboard de profesor', function () {
    $profesor = User::factory()->create(['name' => 'Profesor Test']);
    $profesor->assignRole('profesor');

    $this->actingAs($profesor);

    $page = visit('/dashboard');

    $page->assertPathIs('/dashboard')
        ->assertSee('Panel del Profesor')
        ->assertSee('2024-2025')
        ->assertSee('No tienes secciones asignadas')
        ->assertNoJavaScriptErrors();
});

test('alumno ve el dashboard de estudiante', function () {
    $student = User::factory()->create(['name' => 'Carlos Estudiante']);
    $student->assignRole('alumno');

    Enrollment::factory()
        ->for($this->academicYear)
        ->for($this->grade)
        ->for($this->section)
        ->for($student, 'student')
        ->create();

    $this->actingAs($student);

    $page = visit('/dashboard');

    $page->assertPathIs('/dashboard')
        ->assertSee('Mi Progreso')
        ->assertSee('2024-2025')
        ->assertSee('Sin datos disponibles')
        ->assertSee('No has registrado asistencia aún')
        ->assertNoJavaScriptErrors();
});

test('representante ve el dashboard de representante', function () {
    $student = User::factory()->create(['name' => 'Valentina Rojas']);
    $student->assignRole('alumno');

    Enrollment::factory()
        ->for($this->academicYear)
        ->for($this->grade)
        ->for($this->section)
        ->for($student, 'student')
        ->create();

    $representante = User::factory()->create(['name' => 'Ana Rojas']);
    $representante->assignRole('representante');

    $relationshipType = RelationshipType::firstOrCreate(['name' => 'Padre/Madre']);

    StudentRepresentative::create([
        'student_id' => $student->id,
        'representative_id' => $representante->id,
        'relationship_type_id' => $relationshipType->id,
    ]);

    $this->actingAs($representante);

    $page = visit('/dashboard');

    $page->assertPathIs('/dashboard')
        ->assertSee('Panel del Representante')
        ->assertSee('Valentina Rojas')
        ->assertSee('2024-2025')
        ->assertSee('0.0h')
        ->assertSee('600h')
        ->assertNoJavaScriptErrors();
});

test('usuario no autenticado es redirigido al login desde el dashboard', function () {
    $page = visit('/dashboard');

    $page->assertPathIs('/login');
});
