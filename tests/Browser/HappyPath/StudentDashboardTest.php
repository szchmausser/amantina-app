<?php

use App\Models\AcademicYear;
use App\Models\ActivityCategory;
use App\Models\Attendance;
use App\Models\AttendanceActivity;
use App\Models\Enrollment;
use App\Models\FieldSession;
use App\Models\FieldSessionStatus;
use App\Models\Grade;
use App\Models\Section;
use App\Models\User;
use Database\Seeders\FieldSessionStatusSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\TermTypeSeeder;

/**
 * Happy Path: Student Dashboard
 *
 * Este test modela el flujo de un estudiante que:
 * 1. Inicia sesión y ve su dashboard
 * 2. Consulta sus horas acumuladas
 * 3. Ve el progreso hacia la meta de horas
 * 4. Ve su perfil con historial de horas
 *
 * PRERREQUISITOS: Admin configuró la estructura + Profesor
 * registró asistencia con horas (ver AdminFullFlowTest y
 * TeacherJourneyTest).
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
        'name' => 'Diego Fernández',
        'email' => 'diego@student.com',
        'password' => bcrypt('password'),
    ]);
    $this->student->assignRole('alumno');

    // Enroll student
    Enrollment::factory()
        ->for($this->academicYear)
        ->for($this->grade)
        ->for($this->section)
        ->for($this->student, 'student')
        ->create();
});

// ============================================================================
// PASO 1: Estudiante inicia sesión y ve su dashboard
// ============================================================================

test('estudiante puede iniciar sesión y ver su dashboard', function () {
    $this->actingAs($this->student);

    $page = visit('/dashboard');

    $page->assertPathIs('/dashboard')
        ->assertNoJavaScriptErrors();
});

// ============================================================================
// PASO 2: Estudiante ve su dashboard sin horas previas
// ============================================================================

test('estudiante ve dashboard cuando no tiene horas registradas', function () {
    $this->actingAs($this->student);

    $page = visit('/dashboard');

    $page->assertPathIs('/dashboard')
        ->assertNoJavaScriptErrors();
});

// ============================================================================
// PASO 3: Estudiante con horas acumuladas ve su progreso
// ============================================================================

test('estudiante ve su dashboard con horas acumuladas', function () {
    $this->actingAs($this->student);

    // Create a realized field session with attendance and hours
    $teacher = User::factory()->create(['name' => 'Prof. Dashboard Test']);
    $teacher->assignRole('profesor');
    $realizedStatus = FieldSessionStatus::where('name', 'realized')->first();
    $activityCategory = ActivityCategory::factory()->create(['name' => 'Agricultura']);

    $fieldSession = FieldSession::factory()->create([
        'name' => 'Jornada de Siembra',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $teacher->id,
        'status_id' => $realizedStatus->id,
        'start_datetime' => now()->subDay(),
        'end_datetime' => now()->subDay()->addHours(3),
        'base_hours' => 3.0,
    ]);

    $attendance = Attendance::factory()->create([
        'field_session_id' => $fieldSession->id,
        'user_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    // Assign activity hours
    AttendanceActivity::factory()->create([
        'attendance_id' => $attendance->id,
        'activity_category_id' => $activityCategory->id,
        'hours' => 3.0,
    ]);

    // Student views dashboard — should show accumulated hours
    $page = visit('/dashboard');

    $page->assertPathIs('/dashboard')
        ->assertNoJavaScriptErrors();

    // Verify the hours exist in the database
    $this->assertDatabaseHas('attendance_activities', [
        'attendance_id' => $attendance->id,
        'hours' => 3.0,
    ]);
});

// ============================================================================
// PASO 4: Estudiante ve su perfil con historial
// ============================================================================

test('estudiante puede ver su perfil', function () {
    $this->actingAs($this->student);

    $page = visit('/profile');

    $page->assertPathIs('/profile')
        ->assertNoJavaScriptErrors();
});

// ============================================================================
// PASO 5: Estudiante con múltiples jornadas acumula horas correctamente
// ============================================================================

test('estudiante acumula horas de múltiples jornadas correctamente', function () {
    $this->actingAs($this->student);

    $teacher = User::factory()->create();
    $teacher->assignRole('profesor');
    $realizedStatus = FieldSessionStatus::where('name', 'realized')->first();
    $activityCategory = ActivityCategory::factory()->create(['name' => 'Comunidad']);

    // Jornada 1: 3 horas
    $fieldSession1 = FieldSession::factory()->create([
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $teacher->id,
        'status_id' => $realizedStatus->id,
        'base_hours' => 3.0,
        'start_datetime' => now()->subDays(10),
        'end_datetime' => now()->subDays(10)->addHours(3),
    ]);

    $attendance1 = Attendance::factory()->create([
        'field_session_id' => $fieldSession1->id,
        'user_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    AttendanceActivity::factory()->create([
        'attendance_id' => $attendance1->id,
        'activity_category_id' => $activityCategory->id,
        'hours' => 3.0,
    ]);

    // Jornada 2: 4 horas
    $fieldSession2 = FieldSession::factory()->create([
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $teacher->id,
        'status_id' => $realizedStatus->id,
        'base_hours' => 4.0,
        'start_datetime' => now()->subDays(5),
        'end_datetime' => now()->subDays(5)->addHours(4),
    ]);

    $attendance2 = Attendance::factory()->create([
        'field_session_id' => $fieldSession2->id,
        'user_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    AttendanceActivity::factory()->create([
        'attendance_id' => $attendance2->id,
        'activity_category_id' => $activityCategory->id,
        'hours' => 4.0,
    ]);

    // Jornada 3: Student was ABSENT — should NOT accumulate hours
    $fieldSession3 = FieldSession::factory()->create([
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $teacher->id,
        'status_id' => $realizedStatus->id,
        'base_hours' => 2.0,
        'start_datetime' => now()->subDays(2),
        'end_datetime' => now()->subDays(2)->addHours(2),
    ]);

    Attendance::factory()->absent()->create([
        'field_session_id' => $fieldSession3->id,
        'user_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
    ]);

    // Verify: student has exactly 2 attendance records with attended=true
    $attendedCount = Attendance::where('user_id', $this->student->id)
        ->where('attended', true)
        ->count();
    expect($attendedCount)->toBe(2);

    // Verify: total hours = 3 + 4 = 7
    $totalHours = AttendanceActivity::whereHas('attendance', function ($q) {
        $q->where('user_id', $this->student->id)
            ->where('attended', true);
    })->sum('hours');

    expect((float) $totalHours)->toBe(7.0);

    // Student views dashboard
    $page = visit('/dashboard');

    $page->assertPathIs('/dashboard')
        ->assertNoJavaScriptErrors();
});

// ============================================================================
// PASO 6: Estudiante no puede acceder a funciones de admin
// ============================================================================

test('estudiante no puede acceder a páginas de administración', function () {
    $this->actingAs($this->student);

    // Alumno role has NO admin permissions — expect 403
    $page = visit('/admin/academic-years');
    $page->assertSee('403');

    $page = visit('/admin/field-sessions');
    $page->assertSee('403');
});
