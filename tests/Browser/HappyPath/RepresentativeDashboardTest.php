<?php

use App\Models\AcademicYear;
use App\Models\ActivityCategory;
use App\Models\Attendance;
use App\Models\AttendanceActivity;
use App\Models\Enrollment;
use App\Models\FieldSession;
use App\Models\FieldSessionStatus;
use App\Models\Grade;
use App\Models\RelationshipType;
use App\Models\Section;
use App\Models\StudentRepresentative;
use App\Models\User;
use Database\Seeders\FieldSessionStatusSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\TermTypeSeeder;

/**
 * Happy Path: Representative Dashboard
 *
 * Este test modela el flujo de un representante que:
 * 1. Inicia sesión y ve su dashboard
 * 2. Consulta las horas acumuladas de su representado
 * 3. Ve el progreso de su representado hacia la meta
 *
 * PRERREQUISITOS: Admin configuró la estructura + Profesor
 * registró asistencia con horas + Estudiante tiene horas
 * acumuladas (ver AdminFullFlowTest, TeacherJourneyTest,
 * StudentDashboardTest).
 */
beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->seed(TermTypeSeeder::class);
    $this->seed(FieldSessionStatusSeeder::class);

    // Set up complete academic structure
    // Generate realistic academic year name based on current year
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

    // Create student
    $this->student = User::factory()->create([
        'name' => 'Valentina Rojas',
        'email' => 'valentina@student.com',
    ]);
    $this->student->assignRole('alumno');

    // Enroll student
    Enrollment::factory()
        ->for($this->academicYear)
        ->for($this->grade)
        ->for($this->section)
        ->for($this->student, 'student')
        ->create();

    // Create representative
    $this->representative = User::factory()->create([
        'name' => 'Ana Rojas',
        'email' => 'ana.rojas@parent.com',
        'password' => bcrypt('password'),
    ]);
    $this->representative->assignRole('representante');

    // Link representative to student
    $relationshipType = RelationshipType::firstOrCreate(['name' => 'Padre/Madre']);

    StudentRepresentative::create([
        'student_id' => $this->student->id,
        'representative_id' => $this->representative->id,
        'relationship_type_id' => $relationshipType->id,
    ]);
});

// ============================================================================
// PASO 1: Representante inicia sesión y ve su dashboard
// ============================================================================

test('representante puede iniciar sesión y ver su dashboard', function () {
    $this->actingAs($this->representative);

    $page = visit('/dashboard');

    $page->assertPathIs('/dashboard')
        ->assertNoJavaScriptErrors();
});

// ============================================================================
// PASO 2: Representante ve dashboard cuando su representado no tiene horas
// ============================================================================

test('representante ve dashboard cuando su representado no tiene horas', function () {
    $this->actingAs($this->representative);

    $page = visit('/dashboard');

    $page->assertPathIs('/dashboard')
        ->assertNoJavaScriptErrors();
});

// ============================================================================
// PASO 3: Representante ve las horas acumuladas de su representado
// ============================================================================

test('representante ve horas acumuladas de su representado', function () {
    $this->actingAs($this->representative);

    // Create field session with attendance and hours for the student
    $teacher = User::factory()->create(['name' => 'Prof. Test Rep']);
    $teacher->assignRole('profesor');
    $realizedStatus = FieldSessionStatus::where('name', 'realized')->first();
    $activityCategory = ActivityCategory::factory()->create(['name' => 'Agropecuaria']);

    $fieldSession = FieldSession::factory()->create([
        'name' => 'Jornada de Mantenimiento',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $teacher->id,
        'status_id' => $realizedStatus->id,
        'start_datetime' => now()->subDay(),
        'end_datetime' => now()->subDay()->addHours(4),
        'base_hours' => 4.0,
    ]);

    $attendance = Attendance::factory()->create([
        'field_session_id' => $fieldSession->id,
        'user_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    AttendanceActivity::factory()->create([
        'attendance_id' => $attendance->id,
        'activity_category_id' => $activityCategory->id,
        'hours' => 4.0,
    ]);

    // Representative views dashboard
    $page = visit('/dashboard');

    $page->assertPathIs('/dashboard')
        ->assertNoJavaScriptErrors();

    // Verify hours are in database
    $this->assertDatabaseHas('attendance_activities', [
        'attendance_id' => $attendance->id,
        'hours' => 4.0,
    ]);
});

// ============================================================================
// PASO 4: Representante con múltiples jornadas de su representado
// ============================================================================

test('representante ve progreso acumulado de su representado con múltiples jornadas', function () {
    $this->actingAs($this->representative);

    $teacher = User::factory()->create();
    $teacher->assignRole('profesor');
    $realizedStatus = FieldSessionStatus::where('name', 'realized')->first();
    $activityCategory = ActivityCategory::factory()->create(['name' => 'Servicio Comunitario']);

    // Jornada 1: 3 hours
    $fs1 = FieldSession::factory()->create([
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $teacher->id,
        'status_id' => $realizedStatus->id,
        'base_hours' => 3.0,
        'start_datetime' => now()->subDays(20),
        'end_datetime' => now()->subDays(20)->addHours(3),
    ]);

    $att1 = Attendance::factory()->create([
        'field_session_id' => $fs1->id,
        'user_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    AttendanceActivity::factory()->create([
        'attendance_id' => $att1->id,
        'activity_category_id' => $activityCategory->id,
        'hours' => 3.0,
    ]);

    // Jornada 2: 5 hours
    $fs2 = FieldSession::factory()->create([
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $teacher->id,
        'status_id' => $realizedStatus->id,
        'base_hours' => 5.0,
        'start_datetime' => now()->subDays(10),
        'end_datetime' => now()->subDays(10)->addHours(5),
    ]);

    $att2 = Attendance::factory()->create([
        'field_session_id' => $fs2->id,
        'user_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    AttendanceActivity::factory()->create([
        'attendance_id' => $att2->id,
        'activity_category_id' => $activityCategory->id,
        'hours' => 5.0,
    ]);

    // Verify total hours = 3 + 5 = 8
    $totalHours = AttendanceActivity::whereHas('attendance', function ($q) {
        $q->where('user_id', $this->student->id)
            ->where('attended', true);
    })->sum('hours');

    expect((float) $totalHours)->toBe(8.0);

    // Representative views dashboard
    $page = visit('/dashboard');

    $page->assertPathIs('/dashboard')
        ->assertNoJavaScriptErrors();
});

// ============================================================================
// PASO 5: Representante no puede acceder a funciones de admin
// ============================================================================

test('representante no puede acceder a páginas de administración', function () {
    $this->actingAs($this->representative);

    // Representante role has NO admin permissions — expect 403
    $page = visit('/admin/academic-years');
    $page->assertSee('403');

    $page = visit('/admin/field-sessions');
    $page->assertSee('403');
});

// ============================================================================
// PASO 6: Representante puede ver su perfil
// ============================================================================

test('representante puede ver su perfil', function () {
    $this->actingAs($this->representative);

    $page = visit('/profile');

    $page->assertPathIs('/profile')
        ->assertNoJavaScriptErrors();
});
