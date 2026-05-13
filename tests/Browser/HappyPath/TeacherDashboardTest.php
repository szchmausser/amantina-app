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
use App\Models\Section;
use App\Models\TeacherAssignment;
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

    $currentYear = now()->year;
    $nextYear = $currentYear + 1;
    $testSequence = substr(uniqid(), -4);
    $this->academicYear = AcademicYear::factory()->create([
        'name' => "{$currentYear}-{$nextYear}-T{$testSequence}",
        'is_active' => true,
        'required_hours' => 200,
    ]);

    $this->grade = Grade::factory()->create([
        'name' => '1er Año',
        'academic_year_id' => $this->academicYear->id,
    ]);

    $this->section = Section::factory()->create([
        'name' => 'Sección A',
        'grade_id' => $this->grade->id,
        'academic_year_id' => $this->academicYear->id,
    ]);

    $this->teacher = User::factory()->create([
        'name' => 'Prof. María García',
        'email' => 'maria.garcia@school.com',
        'password' => bcrypt('password'),
    ]);
    $this->teacher->assignRole('profesor');

    TeacherAssignment::factory()->create([
        'user_id' => $this->teacher->id,
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'section_id' => $this->section->id,
    ]);

    $this->activityCategory = ActivityCategory::factory()->create(['name' => 'Siembra y Cosecha']);
    $this->plannedStatus = FieldSessionStatus::where('name', 'planned')->first();

    // Helper closure for creating attendance with hours
    $recordHours = function (User $student, float $totalHours, int $sessionCount = 2) {
        $hoursPerSession = $totalHours / $sessionCount;
        for ($i = 0; $i < $sessionCount; $i++) {
            $session = FieldSession::factory()->create([
                'user_id' => $this->teacher->id,
                'academic_year_id' => $this->academicYear->id,
                'start_datetime' => now()->subDays(10 + $i),
            ]);

            $attendance = Attendance::create([
                'field_session_id' => $session->id,
                'user_id' => $student->id,
                'academic_year_id' => $this->academicYear->id,
                'attended' => true,
            ]);

            AttendanceActivity::create([
                'attendance_id' => $attendance->id,
                'activity_category_id' => $this->activityCategory->id,
                'hours' => $hoursPerSession,
            ]);
        }
    };

    // --- Create students with varying hours ---

    // On Track (180h → 90%)
    $this->onTrackStudent = User::factory()->create(['name' => 'Ana Martínez']);
    $this->onTrackStudent->assignRole('alumno');
    Enrollment::factory()->for($this->academicYear)->for($this->grade)->for($this->section)->for($this->onTrackStudent, 'student')->create();
    $recordHours($this->onTrackStudent, 180, 2);

    // In Progress (120h → 60%)
    $this->inProgressStudent = User::factory()->create(['name' => 'José Hernández']);
    $this->inProgressStudent->assignRole('alumno');
    Enrollment::factory()->for($this->academicYear)->for($this->grade)->for($this->section)->for($this->inProgressStudent, 'student')->create();
    $recordHours($this->inProgressStudent, 120, 2);

    // At Risk (50h → 25%)
    $this->atRiskStudent = User::factory()->create(['name' => 'Pedro López']);
    $this->atRiskStudent->assignRole('alumno');
    Enrollment::factory()->for($this->academicYear)->for($this->grade)->for($this->section)->for($this->atRiskStudent, 'student')->create();
    $recordHours($this->atRiskStudent, 50, 2);

    // Zero hours
    $this->zeroHourStudent = User::factory()->create(['name' => 'Luisa Rojas']);
    $this->zeroHourStudent->assignRole('alumno');
    Enrollment::factory()->for($this->academicYear)->for($this->grade)->for($this->section)->for($this->zeroHourStudent, 'student')->create();

    // Low-attendance student (only 1 attendance → below threshold of 3)
    $this->lowAttendanceStudent = User::factory()->create(['name' => 'Carlos Díaz']);
    $this->lowAttendanceStudent->assignRole('alumno');
    Enrollment::factory()->for($this->academicYear)->for($this->grade)->for($this->section)->for($this->lowAttendanceStudent, 'student')->create();
    $recordHours($this->lowAttendanceStudent, 10, 1);

    // Upcoming sessions — use truly future dates
    $twoDaysFromNow = now()->addDays(2)->setTime(14, 0, 0);
    FieldSession::factory()->create([
        'name' => 'Jornada de Siembra',
        'user_id' => $this->teacher->id,
        'academic_year_id' => $this->academicYear->id,
        'start_datetime' => $twoDaysFromNow,
        'end_datetime' => $twoDaysFromNow->copy()->addHours(2),
        'status_id' => $this->plannedStatus->id,
        'location_name' => 'Huerto Escolar',
    ]);

    $threeDaysFromNow = now()->addDays(3)->setTime(8, 0, 0);
    FieldSession::factory()->create([
        'name' => 'Jornada de Riego',
        'user_id' => $this->teacher->id,
        'academic_year_id' => $this->academicYear->id,
        'start_datetime' => $threeDaysFromNow,
        'end_datetime' => $threeDaysFromNow->copy()->addHours(3),
        'status_id' => $this->plannedStatus->id,
        'location_name' => 'Cancha Deportiva',
    ]);
});

// ============================================================================
// TEST 1: Dashboard loads with all sections visible
// ============================================================================

test('teacher dashboard loads with all expected sections visible', function () {
    $this->actingAs($this->teacher);

    $page = visit('/dashboard');

    $page->assertPathIs('/dashboard')
        ->waitForText('Panel del Profesor')
        ->assertSee($this->academicYear->name)
        ->assertSee('200h requeridas')
        ->assertSee('Registrar Asistencia')
        ->assertSee('Nueva Jornada')
        ->assertSee('Mis Secciones')
        ->assertSee('Reportes')
        ->assertSee('Mis Sesiones')
        ->assertSee('Completadas')
        ->assertSee('Canceladas')
        ->assertSee('Pendientes de Asistencia')
        ->assertSee('Estudiantes Activos')
        ->assertSee('En Meta')
        ->assertSee('En Progreso')
        ->assertSee('En Riesgo')
        ->assertSee('Sin Horas')
        ->assertSee('Mis Secciones')
        ->assertSee('Sección A')
        ->assertSee('Distribución por Categoría')
        ->assertSee('Sesiones por Período')
        ->assertNoJavaScriptErrors();
});

// ============================================================================
// TEST 2: At-risk alert card appears when at-risk students exist
// ============================================================================

test('at risk alert card appears with student data', function () {
    $this->actingAs($this->teacher);

    $page = visit('/dashboard');

    $page->assertVisible('[data-testid="teacher-at-risk-alert"]')
        ->assertSee('Estudiantes en Riesgo')
        ->assertSee('Pedro López')
        ->assertSee('50h')
        ->assertSee('200h')
        ->assertSee('25.0%')
        ->assertNoJavaScriptErrors();
});

// ============================================================================
// TEST 3: Section toggle expands and collapses
// ============================================================================

test('section toggle expands and collapses the student list', function () {
    $this->actingAs($this->teacher);

    $sectionId = $this->section->id;
    $page = visit('/dashboard');

    $page->assertPresent("[data-testid=\"teacher-section-toggle-{$sectionId}\"]")
        ->assertSee('Ver 5 estudiantes');

    $page->assertMissing("[data-testid=\"teacher-section-students-{$sectionId}\"]");

    $page->click("[data-testid=\"teacher-section-toggle-{$sectionId}\"]");

    // Wait for collapse text to appear, which means students grid loaded
    $page->waitForText('Ocultar estudiantes')
        ->assertVisible("[data-testid=\"teacher-section-students-{$sectionId}\"]");

    $page->assertSee('Ana Martínez')
        ->assertSee('Pedro López')
        ->assertSee('Luisa Rojas')
        ->assertSee('Carlos Díaz')
        ->assertSee('José Hernández');

    $page->click("[data-testid=\"teacher-section-toggle-{$sectionId}\"]");

    $page->assertMissing("[data-testid=\"teacher-section-students-{$sectionId}\"]");
    $page->assertSee('Ver 5 estudiantes');
    $page->assertNoJavaScriptErrors();
});

// ============================================================================
// TEST 4: Quick action buttons are rendered
// ============================================================================

test('quick action buttons are rendered with correct data testids', function () {
    $this->actingAs($this->teacher);

    $page = visit('/dashboard');

    $page->assertPresent('[data-testid="teacher-quick-action-registrar-asistencia"]')
        ->assertPresent('[data-testid="teacher-quick-action-nueva-jornada"]')
        ->assertPresent('[data-testid="teacher-quick-action-mis-secciones"]')
        ->assertPresent('[data-testid="teacher-quick-action-reportes"]');

    $page->assertSee('Registrar Asistencia')
        ->assertSee('Nueva Jornada')
        ->assertSee('Mis Secciones');

    $page->assertNoJavaScriptErrors();
});

// ============================================================================
// TEST 5: Upcoming sessions visible
// ============================================================================

test('upcoming sessions section renders with scheduled sessions', function () {
    $this->actingAs($this->teacher);

    $page = visit('/dashboard');

    $page->assertPresent('[data-testid="teacher-upcoming-sessions"]')
        ->assertSee('Jornadas Programadas')
        ->assertSee('Jornada de Siembra')
        ->assertSee('Jornada de Riego')
        ->assertSee('Huerto Escolar')
        ->assertSee('Cancha Deportiva')
        ->assertNoJavaScriptErrors();
});

// ============================================================================
// TEST 6: Distribution card renders with correct labels
// ============================================================================

test('distribution card shows correct category labels', function () {
    $this->actingAs($this->teacher);

    $page = visit('/dashboard');

    $page->assertPresent('[data-testid="teacher-dist-on-track"]')
        ->assertPresent('[data-testid="teacher-dist-in-progress"]')
        ->assertPresent('[data-testid="teacher-dist-at-risk"]')
        ->assertPresent('[data-testid="teacher-dist-zero-hours"]');

    $page->assertSee('En Meta')
        ->assertSee('En Progreso')
        ->assertSee('En Riesgo')
        ->assertSee('Sin Horas');

    $page->assertSee('Estudiantes Activos: 5');

    $page->assertNoJavaScriptErrors();
});

// ============================================================================
// TEST 7: Low attendance toggle expands and collapses
// ============================================================================

test('low attendance card toggle expands and collapses the student list', function () {
    $this->actingAs($this->teacher);

    $page = visit('/dashboard');

    $page->waitForText('Estudiantes con Baja Asistencia');

    // Toggle button must be present
    $page->assertPresent('[data-testid="teacher-low-attendance-toggle"]');

    // List must be hidden initially
    $page->assertMissing('[data-testid="teacher-low-attendance-list"]');

    // Click to expand
    $page->click('[data-testid="teacher-low-attendance-toggle"]');

    // List visible after expand, and student data shown
    $page->waitForText('Carlos Díaz')
        ->assertVisible('[data-testid="teacher-low-attendance-list"]');

    // Collapse text should appear
    $page->assertSee('Ocultar');

    // Click to collapse
    $page->click('[data-testid="teacher-low-attendance-toggle"]');

    // List hidden again
    $page->assertMissing('[data-testid="teacher-low-attendance-list"]');

    $page->assertNoJavaScriptErrors();
});

// ============================================================================
// TEST 8: Rankings cards are rendered
// ============================================================================

test('rankings cards are rendered for outstanding top hours and at risk', function () {
    $this->actingAs($this->teacher);

    $page = visit('/dashboard');

    $page->assertPresent('[data-testid="teacher-outstanding-card"]');
    $page->assertPresent('[data-testid="teacher-top-hours-card"]')
        ->assertSee('Alumnos con más horas acumuladas');

    $page->assertSee('Estudiantes que Necesitan Apoyo')
        ->assertSee('Pedro López');

    $page->assertNoJavaScriptErrors();
});

// ============================================================================
// TEST 9: Section card shows distribution badges
// ============================================================================

test('section card shows distribution badges with progress info', function () {
    $this->actingAs($this->teacher);

    $sectionId = $this->section->id;
    $page = visit('/dashboard');

    $page->assertPresent("[data-testid=\"teacher-section-{$sectionId}\"]")
        ->assertSee('Sección A')
        ->assertSee('1er Año');

    $page->assertSee('en meta')
        ->assertSee('progreso')
        ->assertSee('riesgo')
        ->assertSee('sin horas');

    $page->assertSee('Progreso promedio');
    $page->assertNoJavaScriptErrors();
});

// ============================================================================
// TEST 10: Empty state for teacher without sections
// ============================================================================

test('teacher with no sections sees empty state message', function () {
    $unassignedTeacher = User::factory()->create(['name' => 'Prof. Sin Secciones']);
    $unassignedTeacher->assignRole('profesor');

    $this->actingAs($unassignedTeacher);

    $page = visit('/dashboard');

    $page->assertPathIs('/dashboard')
        ->assertSee('Panel del Profesor')
        ->assertSee('No tienes secciones asignadas')
        ->assertNoJavaScriptErrors();
});
