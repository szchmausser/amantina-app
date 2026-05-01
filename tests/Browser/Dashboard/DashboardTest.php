<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\RelationshipType;
use App\Models\Section;
use App\Models\StudentRepresentative;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

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
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin);

    $page = visit('/dashboard');

    $page->assertPathIs('/dashboard')
        ->assertNoJavaScriptErrors();
});

test('profesor ve el dashboard de profesor', function () {
    $profesor = User::factory()->create();
    $profesor->assignRole('profesor');

    $this->actingAs($profesor);

    $page = visit('/dashboard');

    $page->assertPathIs('/dashboard')
        ->assertNoJavaScriptErrors();
});

test('alumno ve el dashboard de estudiante', function () {
    $student = User::factory()->create();
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
        ->assertNoJavaScriptErrors();
});

test('representante ve el dashboard de representante', function () {
    $student = User::factory()->create();
    $student->assignRole('alumno');

    Enrollment::factory()
        ->for($this->academicYear)
        ->for($this->grade)
        ->for($this->section)
        ->for($student, 'student')
        ->create();

    $representante = User::factory()->create();
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
        ->assertNoJavaScriptErrors();
});

test('usuario no autenticado es redirigido al login desde el dashboard', function () {
    $page = visit('/dashboard');

    $page->assertPathIs('/login');
});
