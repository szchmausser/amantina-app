<?php

namespace Tests\Browser\HappyPath;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\FieldSession;
use App\Models\FieldSessionStatus;
use App\Models\Grade;
use App\Models\Section;
use App\Models\User;
use Database\Seeders\FieldSessionStatusSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Pest\Browser\Browsable;

uses(DatabaseTruncation::class);
uses(Browsable::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->seed(FieldSessionStatusSeeder::class);

    $this->admin = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
    ]);
    $this->admin->assignRole('admin');

    $this->profesor = User::factory()->create(['name' => 'Prof. Martínez']);
    $this->profesor->assignRole('profesor');

    $this->actingAs($this->admin);

    $this->academicYear = AcademicYear::factory()->create([
        'name' => '2024-2025',
        'is_active' => true,
    ]);

    $this->grade = Grade::factory()->for($this->academicYear)->create(['name' => '1er Año']);
    $this->section = Section::factory()->for($this->academicYear)->for($this->grade)->create(['name' => 'A']);

    $this->student = User::factory()->create(['name' => 'Luis Estudiante']);
    $this->student->assignRole('alumno');

    Enrollment::factory()
        ->for($this->academicYear)
        ->for($this->grade)
        ->for($this->section)
        ->for($this->student, 'student')
        ->create();

    $realizedStatus = FieldSessionStatus::where('name', 'realized')->first();

    $this->fieldSession = FieldSession::factory()->create([
        'name' => 'Jornada de Siembra',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $this->profesor->id,
        'status_id' => $realizedStatus->id,
        'start_datetime' => now()->subDay(),
        'end_datetime' => now()->subDay()->addHours(2),
        'base_hours' => 2.0,
    ]);
});

test('admin puede acceder a la pantalla de asistencia de una jornada', function () {
    $page = visit("/admin/field-sessions/{$this->fieldSession->id}/attendance");

    $page->assertPathIs("/admin/field-sessions/{$this->fieldSession->id}/attendance")
        ->assertSee('Jornada de Siembra')
        ->assertNoJavaScriptErrors();
});

test('pantalla de asistencia muestra los estudiantes inscritos', function () {
    $page = visit("/admin/field-sessions/{$this->fieldSession->id}/attendance");

    $page->assertSee('Luis Estudiante')
        ->assertNoJavaScriptErrors();
});

test('asistencia registrada aparece en la pantalla', function () {
    Attendance::factory()->create([
        'field_session_id' => $this->fieldSession->id,
        'user_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    $page = visit("/admin/field-sessions/{$this->fieldSession->id}/attendance");

    $page->assertSee('Luis Estudiante')
        ->assertNoJavaScriptErrors();
});

test('profesor puede acceder a la asistencia de sus propias jornadas', function () {
    $this->actingAs($this->profesor);

    $page = visit("/admin/field-sessions/{$this->fieldSession->id}/attendance");

    $page->assertPathIs("/admin/field-sessions/{$this->fieldSession->id}/attendance")
        ->assertSee('Jornada de Siembra')
        ->assertNoJavaScriptErrors();
});

test('alumno no puede acceder a la pantalla de asistencia', function () {
    $this->actingAs($this->student);

    $page = visit("/admin/field-sessions/{$this->fieldSession->id}/attendance");

    // El alumno recibe un error 403
    $page->assertSee('403');
});

test('representante NO puede acceder a la pantalla de asistencia', function () {
    $representante = User::factory()->create();
    $representante->assignRole('representante');

    $this->actingAs($representante);

    $page = visit("/admin/field-sessions/{$this->fieldSession->id}/attendance");

    $page->assertSee('403');
});
