<?php

namespace Tests\Browser\HappyPath;

use App\Models\AcademicYear;
use App\Models\ActivityCategory;
use App\Models\Attendance;
use App\Models\AttendanceActivity;
use App\Models\Enrollment;
use App\Models\FieldSession;
use App\Models\FieldSessionStatus;
use App\Models\Grade;
use App\Models\SchoolTerm;
use App\Models\Section;
use App\Models\TeacherAssignment;
use App\Models\TermType;
use App\Models\User;
use Database\Seeders\FieldSessionStatusSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\TermTypeSeeder;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Pest\Browser\Browsable;

uses(DatabaseTruncation::class);
uses(Browsable::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->seed(TermTypeSeeder::class);
    $this->seed(FieldSessionStatusSeeder::class);

    $currentYear = now()->year;
    $nextYear = $currentYear + 1;
    $testSequence = substr(uniqid(), -4);
    $this->academicYear = AcademicYear::factory()->create([
        'name' => "{$currentYear}-{$nextYear}-T{$testSequence}",
        'is_active' => true,
        'required_hours' => 600,
    ]);

    $this->grade = Grade::factory()->for($this->academicYear)->create([
        'name' => '1er Año',
        'order' => 1,
    ]);

    $this->section = Section::factory()->for($this->academicYear)->for($this->grade)->create([
        'name' => 'Sección A',
    ]);

    $termType = TermType::where('name', 'Lapso 1')->first();
    $this->schoolTerm = SchoolTerm::factory()->create([
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $termType->id,
        'start_date' => '2025-09-15',
        'end_date' => '2025-12-15',
    ]);

    $this->teacher = User::factory()->create([
        'name' => 'Prof. Carmen Ruiz',
        'email' => 'carmen.ruiz@school.com',
        'password' => bcrypt('password'),
    ]);
    $this->teacher->assignRole('profesor');

    TeacherAssignment::factory()->create([
        'user_id' => $this->teacher->id,
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'section_id' => $this->section->id,
    ]);

    $this->student1 = User::factory()->create(['name' => 'Lucía Mendoza']);
    $this->student1->assignRole('alumno');

    $this->student2 = User::factory()->create(['name' => 'Pedro Sánchez']);
    $this->student2->assignRole('alumno');

    Enrollment::factory()->for($this->academicYear)->for($this->grade)->for($this->section)->for($this->student1, 'student')->create();
    Enrollment::factory()->for($this->academicYear)->for($this->grade)->for($this->section)->for($this->student2, 'student')->create();

    $this->activityCategory = ActivityCategory::factory()->create([
        'name' => 'Siembra y Cosecha',
    ]);

    $this->plannedStatus = FieldSessionStatus::where('name', 'planned')->first();
    $this->realizedStatus = FieldSessionStatus::where('name', 'realized')->first();
});

// ============================================================================
// PASO 1: Profesor ve su dashboard
// ============================================================================

test('profesor puede iniciar sesión y ver su dashboard', function () {
    $this->actingAs($this->teacher);

    $page = visit('/dashboard');

    $page->assertPathIs('/dashboard')
        ->assertSee('Sección A')
        ->assertSee('Prof. Carmen Ruiz')
        ->assertNoJavaScriptErrors();
});

// ============================================================================
// PASO 2: Profesor navega al formulario de creación
// ============================================================================

test('profesor puede navegar al formulario de creación de jornada', function () {
    $this->actingAs($this->teacher);

    $page = visit('/admin/field-sessions');

    $page->assertPathIs('/admin/field-sessions')
        ->assertSee('Jornadas')
        ->assertNoJavaScriptErrors();

    $page = visit('/admin/field-sessions/create');

    $page->assertPathIs('/admin/field-sessions/create')
        ->assertNoJavaScriptErrors();
});

// ============================================================================
// PASO 3: Profesor ve estudiantes en pantalla de asistencia
// ============================================================================

test('profesor puede ver estudiantes en la pantalla de asistencia', function () {
    $this->actingAs($this->teacher);

    $fieldSession = FieldSession::factory()->create([
        'name' => 'Jornada de Limpieza',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $this->teacher->id,
        'status_id' => $this->realizedStatus->id,
        'start_datetime' => now()->subDay(),
        'end_datetime' => now()->subDay()->addHours(2),
        'base_hours' => 2.0,
    ]);

    $page = visit("/admin/field-sessions/{$fieldSession->id}/attendance");

    $page->assertPathIs("/admin/field-sessions/{$fieldSession->id}/attendance")
        ->assertSee('Jornada de Limpieza')
        ->assertSee('Lucía Mendoza')
        ->assertSee('Pedro Sánchez')
        ->assertNoJavaScriptErrors();
});

// ============================================================================
// PASO 4: Profesor ve asistencia registrada en pantalla
// ============================================================================

test('profesor puede ver asistencia registrada en la pantalla', function () {
    $this->actingAs($this->teacher);

    $fieldSession = FieldSession::factory()->create([
        'name' => 'Jornada de Riego',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $this->teacher->id,
        'status_id' => $this->realizedStatus->id,
        'start_datetime' => now()->subDay(),
        'end_datetime' => now()->subDay()->addHours(2),
        'base_hours' => 2.0,
    ]);

    // Pre-create attendance via factory (setup, not the action under test)
    Attendance::factory()->create([
        'field_session_id' => $fieldSession->id,
        'user_id' => $this->student1->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    Attendance::factory()->create([
        'field_session_id' => $fieldSession->id,
        'user_id' => $this->student2->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    $page = visit("/admin/field-sessions/{$fieldSession->id}/attendance");

    $page->assertSee('Lucía Mendoza')
        ->assertSee('Pedro Sánchez')
        ->assertNoJavaScriptErrors();
});

// ============================================================================
// PASO 5: Profesor ve detalle de jornada con asistencia y horas
// ============================================================================

test('profesor puede ver el detalle de una jornada con asistencia y horas asignadas', function () {
    $this->actingAs($this->teacher);

    $fieldSession = FieldSession::factory()->create([
        'name' => 'Jornada Integrada: Huerto Escolar',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $this->teacher->id,
        'status_id' => $this->realizedStatus->id,
        'start_datetime' => now()->subHours(5),
        'end_datetime' => now()->subHours(2),
        'base_hours' => 3.0,
    ]);

    // Pre-create attendance and hours via factories (setup)
    $attendance1 = Attendance::factory()->create([
        'field_session_id' => $fieldSession->id,
        'user_id' => $this->student1->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    Attendance::factory()->create([
        'field_session_id' => $fieldSession->id,
        'user_id' => $this->student2->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    AttendanceActivity::factory()->create([
        'attendance_id' => $attendance1->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 3.0,
    ]);

    // Verify detail page shows the jornada
    $page = visit("/admin/field-sessions/{$fieldSession->id}");

    $page->assertSee('Jornada Integrada: Huerto Escolar')
        ->assertNoJavaScriptErrors();

    // Verify attendance page shows students
    $page = visit("/admin/field-sessions/{$fieldSession->id}/attendance");

    $page->assertSee('Lucía Mendoza')
        ->assertSee('Pedro Sánchez')
        ->assertNoJavaScriptErrors();

    // Verify listing shows the jornada
    $page = visit('/admin/field-sessions');

    $page->assertSee('Jornada Integrada: Huerto Escolar')
        ->assertNoJavaScriptErrors();
});
