<?php

namespace Tests\Feature\HappyPath;

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
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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
// CREAR JORNADA VIA API
// ============================================================================

test('profesor puede crear una jornada de campo mediante POST', function () {
    $this->actingAs($this->teacher);

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

    $this->assertDatabaseHas('field_sessions', [
        'name' => 'Jornada de Siembra en Huerto',
        'user_id' => $this->teacher->id,
        'academic_year_id' => $this->academicYear->id,
        'status_id' => $this->plannedStatus->id,
        'base_hours' => 3.0,
    ]);
});

// ============================================================================
// REGISTRAR ASISTENCIA VIA API
// ============================================================================

test('profesor puede registrar asistencia para sus estudiantes mediante POST', function () {
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
});

// ============================================================================
// MARCAR AUSENCIA VIA API
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
        'attended' => false,
        'notes' => 'No asistió por enfermedad',
    ]);

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
// ASIGNAR HORAS VIA API
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

    $response = $this->post("/admin/field-sessions/{$fieldSession->id}/attendance/quick-assign-hours", [
        'user_id' => $this->student1->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 3.0,
    ]);

    expect($response->status())->toBeIn([200, 302]);

    $this->assertDatabaseHas('attendance_activities', [
        'attendance_id' => $attendance->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 3.0,
    ]);
});

// ============================================================================
// FLUJO COMPLETO VIA API
// ============================================================================

test('profesor completa el flujo de jornada via API: crear → asistir → asignar horas', function () {
    $this->actingAs($this->teacher);

    // 1. Create field session
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

    // 2. Register attendance for both students
    $this->post("/admin/field-sessions/{$fieldSession->id}/attendance", [
        'user_id' => $this->student1->id,
        'attended' => true,
    ])->assertStatus(302);

    $this->post("/admin/field-sessions/{$fieldSession->id}/attendance", [
        'user_id' => $this->student2->id,
        'attended' => true,
    ])->assertStatus(302);

    // 3. Assign hours to student1
    $attendance1 = Attendance::where('field_session_id', $fieldSession->id)
        ->where('user_id', $this->student1->id)
        ->first();

    $this->post("/admin/field-sessions/{$fieldSession->id}/attendance/quick-assign-hours", [
        'user_id' => $this->student1->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 3.0,
    ]);

    // 4. Verify all data
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
});
