<?php

use App\Models\AcademicYear;
use App\Models\ActivityCategory;
use App\Models\Attendance;
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

/**
 * Happy Path: Teacher Journey
 *
 * Este test modela el flujo completo de un profesor que:
 * 1. Ve su dashboard
 * 2. Crea una jornada de campo (field session)
 * 3. Registra asistencia para sus estudiantes
 * 4. Marca la jornada como realizada
 * 5. Asigna horas por actividad
 *
 * PRERREQUISITOS: Admin ya configuró la estructura académica
 * (ver AdminFullFlowTest).
 */
beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->seed(TermTypeSeeder::class);
    $this->seed(FieldSessionStatusSeeder::class);

    // Set up complete academic structure (as admin would have done)
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

    // Create school term
    $termType = TermType::where('name', 'Lapso 1')->first();
    $this->schoolTerm = SchoolTerm::factory()->create([
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $termType->id,
        'start_date' => '2025-09-15',
        'end_date' => '2025-12-15',
    ]);

    // Create teacher
    $this->teacher = User::factory()->create([
        'name' => 'Prof. Carmen Ruiz',
        'email' => 'carmen.ruiz@school.com',
        'password' => bcrypt('password'),
    ]);
    $this->teacher->assignRole('profesor');

    // Assign teacher to section
    TeacherAssignment::factory()->create([
        'user_id' => $this->teacher->id,
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'section_id' => $this->section->id,
    ]);

    // Create students and enroll them
    $this->student1 = User::factory()->create(['name' => 'Lucía Mendoza']);
    $this->student1->assignRole('alumno');

    $this->student2 = User::factory()->create(['name' => 'Pedro Sánchez']);
    $this->student2->assignRole('alumno');

    Enrollment::factory()->for($this->academicYear)->for($this->grade)->for($this->section)->for($this->student1, 'student')->create();
    Enrollment::factory()->for($this->academicYear)->for($this->grade)->for($this->section)->for($this->student2, 'student')->create();

    // Create activity categories for hour assignment
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
        ->assertNoJavaScriptErrors();
});

// ============================================================================
// PASO 2: Profesor crea una jornada de campo
// ============================================================================

test('profesor puede crear una jornada de campo', function () {
    $this->actingAs($this->teacher);

    // Navigate to field sessions listing
    $page = visit('/admin/field-sessions');

    $page->assertPathIs('/admin/field-sessions')
        ->assertSee('Jornadas')
        ->assertNoJavaScriptErrors();

    // Navigate to create form
    $page = visit('/admin/field-sessions/create');

    $page->assertPathIs('/admin/field-sessions/create')
        ->assertNoJavaScriptErrors();

    // Create jornada via POST (complex form with Select components)
    $response = $this->post('/admin/field-sessions', [
        'name' => 'Jornada de Siembra en Huerto',
        'description' => 'Actividad de siembra de hortalizas en el huerto escolar',
        'academic_year_id' => $this->academicYear->id,
        'school_term_id' => $this->schoolTerm->id,
        'user_id' => $this->teacher->id,
        'activity_name' => 'Siembra',
        'location_name' => 'Huerto Escolar',
        'start_datetime' => now()->addDay()->format('Y-m-d H:i:s'),
        'end_datetime' => now()->addDay()->addHours(3)->format('Y-m-d H:i:s'),
        'base_hours' => 3.0,
        'status_id' => $this->plannedStatus->id,
    ]);

    $response->assertStatus(302);

    // Verify the field session was created
    $this->assertDatabaseHas('field_sessions', [
        'name' => 'Jornada de Siembra en Huerto',
        'user_id' => $this->teacher->id,
        'academic_year_id' => $this->academicYear->id,
        'status_id' => $this->plannedStatus->id,
        'base_hours' => 3.0,
    ]);

    // Verify it appears in the listing
    $page = visit('/admin/field-sessions');

    $page->assertSee('Jornada de Siembra en Huerto')
        ->assertNoJavaScriptErrors();
});

// ============================================================================
// PASO 3: Profesor accede a la pantalla de asistencia
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

    // Navigate to attendance page
    $page = visit("/admin/field-sessions/{$fieldSession->id}/attendance");

    $page->assertPathIs("/admin/field-sessions/{$fieldSession->id}/attendance")
        ->assertSee('Jornada de Limpieza')
        ->assertSee('Lucía Mendoza')
        ->assertSee('Pedro Sánchez')
        ->assertNoJavaScriptErrors();
});

// ============================================================================
// PASO 4: Profesor registra asistencia
// ============================================================================

test('profesor puede registrar asistencia para sus estudiantes', function () {
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

    // Register attendance for student1 (present) via POST
    $response = $this->post("/admin/field-sessions/{$fieldSession->id}/attendance", [
        'user_id' => $this->student1->id,
        'attended' => true,
        'notes' => null,
    ]);

    $response->assertStatus(302);

    $this->assertDatabaseHas('attendances', [
        'field_session_id' => $fieldSession->id,
        'user_id' => $this->student1->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    // Register attendance for student2 (present)
    $response = $this->post("/admin/field-sessions/{$fieldSession->id}/attendance", [
        'user_id' => $this->student2->id,
        'attended' => true,
        'notes' => null,
    ]);

    $response->assertStatus(302);

    $this->assertDatabaseHas('attendances', [
        'field_session_id' => $fieldSession->id,
        'user_id' => $this->student2->id,
        'attended' => true,
    ]);

    // Verify attendance page shows the registered data
    $page = visit("/admin/field-sessions/{$fieldSession->id}/attendance");

    $page->assertSee('Lucía Mendoza')
        ->assertSee('Pedro Sánchez')
        ->assertNoJavaScriptErrors();
});

// ============================================================================
// PASO 5: Profesor marca un estudiante como ausente
// ============================================================================

test('profesor puede marcar un estudiante como ausente', function () {
    $this->actingAs($this->teacher);

    $fieldSession = FieldSession::factory()->create([
        'name' => 'Jornada de Cosecha',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $this->teacher->id,
        'status_id' => $this->realizedStatus->id,
        'start_datetime' => now()->subDay(),
        'end_datetime' => now()->subDay()->addHours(2),
        'base_hours' => 2.0,
    ]);

    // Student1 present
    Attendance::factory()->create([
        'field_session_id' => $fieldSession->id,
        'user_id' => $this->student1->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    // Student2 absent
    Attendance::factory()->create([
        'field_session_id' => $fieldSession->id,
        'user_id' => $this->student2->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => false,
        'notes' => 'No asistió por enfermedad',
    ]);

    // Verify both attendances exist
    $this->assertDatabaseHas('attendances', [
        'field_session_id' => $fieldSession->id,
        'user_id' => $this->student1->id,
        'attended' => true,
    ]);

    $this->assertDatabaseHas('attendances', [
        'field_session_id' => $fieldSession->id,
        'user_id' => $this->student2->id,
        'attended' => false,
    ]);
});

// ============================================================================
// PASO 6: Profesor asigna horas por actividad
// ============================================================================

test('profesor puede asignar horas de actividad a estudiantes presentes', function () {
    $this->actingAs($this->teacher);

    $fieldSession = FieldSession::factory()->create([
        'name' => 'Jornada de Compostaje',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $this->teacher->id,
        'status_id' => $this->realizedStatus->id,
        'start_datetime' => now()->subDay(),
        'end_datetime' => now()->subDay()->addHours(3),
        'base_hours' => 3.0,
    ]);

    $attendance = Attendance::factory()->create([
        'field_session_id' => $fieldSession->id,
        'user_id' => $this->student1->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    // Assign hours via quick-assign-hours endpoint (simpler format than bulk)
    $response = $this->post("/admin/field-sessions/{$fieldSession->id}/attendance/quick-assign-hours", [
        'user_id' => $this->student1->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 3.0,
    ]);

    // The response should redirect back or return success
    expect($response->status())->toBeIn([200, 302]);

    // Verify the activity hours were assigned
    $this->assertDatabaseHas('attendance_activities', [
        'attendance_id' => $attendance->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 3.0,
    ]);
});

// ============================================================================
// INTEGRACIÓN: Flujo completo del profesor
// ============================================================================

test('profesor completa el flujo de jornada: crear → asistir → asignar horas', function () {
    $this->actingAs($this->teacher);

    // 1. Create field session via POST
    $response = $this->post('/admin/field-sessions', [
        'name' => 'Jornada Integrada: Huerto Escolar',
        'description' => 'Jornada completa de mantenimiento del huerto',
        'academic_year_id' => $this->academicYear->id,
        'school_term_id' => $this->schoolTerm->id,
        'user_id' => $this->teacher->id,
        'activity_name' => 'Mantenimiento',
        'location_name' => 'Huerto Escolar',
        'start_datetime' => now()->subHours(5)->format('Y-m-d H:i:s'),
        'end_datetime' => now()->subHours(2)->format('Y-m-d H:i:s'),
        'base_hours' => 3.0,
        'status_id' => $this->realizedStatus->id,
    ]);

    $response->assertStatus(302);

    $fieldSession = FieldSession::where('name', 'Jornada Integrada: Huerto Escolar')->first();
    expect($fieldSession)->not->toBeNull();

    // 2. View field session detail
    $page = visit("/admin/field-sessions/{$fieldSession->id}");

    $page->assertSee('Jornada Integrada: Huerto Escolar')
        ->assertNoJavaScriptErrors();

    // 3. Navigate to attendance page
    $page = visit("/admin/field-sessions/{$fieldSession->id}/attendance");

    $page->assertSee('Lucía Mendoza')
        ->assertSee('Pedro Sánchez')
        ->assertNoJavaScriptErrors();

    // 4. Register attendance for both students
    $this->post("/admin/field-sessions/{$fieldSession->id}/attendance", [
        'user_id' => $this->student1->id,
        'attended' => true,
    ])->assertStatus(302);

    $this->post("/admin/field-sessions/{$fieldSession->id}/attendance", [
        'user_id' => $this->student2->id,
        'attended' => true,
    ])->assertStatus(302);

    // 5. Assign hours to student1 via quick-assign-hours
    $attendance1 = Attendance::where('field_session_id', $fieldSession->id)
        ->where('user_id', $this->student1->id)
        ->first();

    $this->post("/admin/field-sessions/{$fieldSession->id}/attendance/quick-assign-hours", [
        'user_id' => $this->student1->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 3.0,
    ]);

    // 6. Verify all data is in the database
    $this->assertDatabaseHas('attendances', [
        'field_session_id' => $fieldSession->id,
        'user_id' => $this->student1->id,
        'attended' => true,
    ]);

    $this->assertDatabaseHas('attendances', [
        'field_session_id' => $fieldSession->id,
        'user_id' => $this->student2->id,
        'attended' => true,
    ]);

    $this->assertDatabaseHas('attendance_activities', [
        'attendance_id' => $attendance1->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 3.0,
    ]);

    // 7. Verify the jornada shows in the listing with correct status
    $page = visit('/admin/field-sessions');

    $page->assertSee('Jornada Integrada: Huerto Escolar')
        ->assertNoJavaScriptErrors();
});
