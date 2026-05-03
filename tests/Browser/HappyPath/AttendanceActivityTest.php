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

    $this->alumno = User::factory()->create(['name' => 'Luis Estudiante']);
    $this->alumno->assignRole('alumno');

    $this->representante = User::factory()->create(['name' => 'Representante Pérez']);
    $this->representante->assignRole('representante');

    $this->academicYear = AcademicYear::factory()->create([
        'name' => '2024-2025',
        'is_active' => true,
    ]);

    $this->grade = Grade::factory()->for($this->academicYear)->create(['name' => '1er Año']);
    $this->section = Section::factory()->for($this->academicYear)->for($this->grade)->create(['name' => 'A']);

    Enrollment::factory()
        ->for($this->academicYear)
        ->for($this->grade)
        ->for($this->section)
        ->for($this->alumno, 'student')
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

    $this->attendance = Attendance::factory()->create([
        'field_session_id' => $this->fieldSession->id,
        'user_id' => $this->alumno->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    $this->activityCategory = ActivityCategory::factory()->create([
        'name' => 'Siembra',
    ]);
});

// ============================================================================
// TESTS DE SEGURIDAD - ADMIN
// ============================================================================

test('admin SÍ puede crear actividad de asistencia mediante POST', function () {
    $this->actingAs($this->admin);

    $response = $this->post('/admin/attendance-activities', [
        'attendance_id' => $this->attendance->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 1.5,
        'notes' => 'Preparación del terreno',
    ]);

    $response->assertStatus(302); // Redirección exitosa

    // Verificar que SÍ se creó la actividad
    $this->assertDatabaseHas('attendance_activities', [
        'attendance_id' => $this->attendance->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 1.5,
        'notes' => 'Preparación del terreno',
    ]);
});

test('admin SÍ puede editar actividad de asistencia mediante PUT', function () {
    $activity = AttendanceActivity::factory()->create([
        'attendance_id' => $this->attendance->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 1.0,
        'notes' => 'Actividad original',
    ]);

    $this->actingAs($this->admin);

    $response = $this->put("/admin/attendance-activities/{$activity->id}", [
        'attendance_id' => $this->attendance->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 2.0,
        'notes' => 'Actividad modificada por admin',
    ]);

    $response->assertStatus(302); // Redirección exitosa

    // Verificar que SÍ se modificó
    $this->assertDatabaseHas('attendance_activities', [
        'id' => $activity->id,
        'hours' => 2.0,
        'notes' => 'Actividad modificada por admin',
    ]);
});

test('admin SÍ puede eliminar actividad de asistencia mediante DELETE', function () {
    $activity = AttendanceActivity::factory()->create([
        'attendance_id' => $this->attendance->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 1.0,
    ]);

    $this->actingAs($this->admin);

    $response = $this->delete("/admin/attendance-activities/{$activity->id}");

    $response->assertStatus(302); // Redirección exitosa

    // Verificar que SÍ se eliminó (soft delete)
    $this->assertSoftDeleted('attendance_activities', [
        'id' => $activity->id,
    ]);
});

// ============================================================================
// TESTS DE SEGURIDAD - PROFESOR (JORNADAS PROPIAS)
// ============================================================================

test('profesor SÍ puede crear actividad en asistencia de su propia jornada mediante POST', function () {
    $this->actingAs($this->profesor);

    $response = $this->post('/admin/attendance-activities', [
        'attendance_id' => $this->attendance->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 1.5,
        'notes' => 'Riego de plantas',
    ]);

    $response->assertStatus(302); // Redirección exitosa

    // Verificar que SÍ se creó la actividad
    $this->assertDatabaseHas('attendance_activities', [
        'attendance_id' => $this->attendance->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 1.5,
        'notes' => 'Riego de plantas',
    ]);
});

test('profesor SÍ puede editar actividad en asistencia de su propia jornada mediante PUT', function () {
    $activity = AttendanceActivity::factory()->create([
        'attendance_id' => $this->attendance->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 1.0,
        'notes' => 'Actividad original',
    ]);

    $this->actingAs($this->profesor);

    $response = $this->put("/admin/attendance-activities/{$activity->id}", [
        'attendance_id' => $this->attendance->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 1.5,
        'notes' => 'Actividad modificada por profesor',
    ]);

    $response->assertStatus(302); // Redirección exitosa

    // Verificar que SÍ se modificó
    $this->assertDatabaseHas('attendance_activities', [
        'id' => $activity->id,
        'hours' => 1.5,
        'notes' => 'Actividad modificada por profesor',
    ]);
});

test('profesor SÍ puede eliminar actividad en asistencia de su propia jornada mediante DELETE', function () {
    $activity = AttendanceActivity::factory()->create([
        'attendance_id' => $this->attendance->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 1.0,
    ]);

    $this->actingAs($this->profesor);

    $response = $this->delete("/admin/attendance-activities/{$activity->id}");

    $response->assertStatus(302); // Redirección exitosa

    // Verificar que SÍ se eliminó (soft delete)
    $this->assertSoftDeleted('attendance_activities', [
        'id' => $activity->id,
    ]);
});

// ============================================================================
// TESTS DE SEGURIDAD - PROFESOR (JORNADAS AJENAS)
// ============================================================================

test('profesor NO puede crear actividad en asistencia de jornada ajena mediante POST', function () {
    // Crear otro profesor y su jornada
    $otroProfesor = User::factory()->create(['name' => 'Prof. García']);
    $otroProfesor->assignRole('profesor');

    $jornadaAjena = FieldSession::factory()->create([
        'name' => 'Jornada de Otro Profesor',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $otroProfesor->id,
        'status_id' => FieldSessionStatus::where('name', 'realized')->first()->id,
    ]);

    $attendanceAjena = Attendance::factory()->create([
        'field_session_id' => $jornadaAjena->id,
        'user_id' => $this->alumno->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    $this->actingAs($this->profesor);

    $response = $this->post('/admin/attendance-activities', [
        'attendance_id' => $attendanceAjena->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 1.5,
        'notes' => 'Intento en jornada ajena',
    ]);

    $response->assertStatus(403);

    // Verificar que NO se creó la actividad
    $this->assertDatabaseMissing('attendance_activities', [
        'attendance_id' => $attendanceAjena->id,
        'notes' => 'Intento en jornada ajena',
    ]);
});

test('profesor NO puede editar actividad en asistencia de jornada ajena mediante PUT', function () {
    // Crear otro profesor y su jornada
    $otroProfesor = User::factory()->create(['name' => 'Prof. Sánchez']);
    $otroProfesor->assignRole('profesor');

    $jornadaAjena = FieldSession::factory()->create([
        'name' => 'Jornada Ajena',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $otroProfesor->id,
        'status_id' => FieldSessionStatus::where('name', 'realized')->first()->id,
    ]);

    $attendanceAjena = Attendance::factory()->create([
        'field_session_id' => $jornadaAjena->id,
        'user_id' => $this->alumno->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    $activity = AttendanceActivity::factory()->create([
        'attendance_id' => $attendanceAjena->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 1.0,
        'notes' => 'Actividad original',
    ]);

    $this->actingAs($this->profesor);

    $response = $this->put("/admin/attendance-activities/{$activity->id}", [
        'attendance_id' => $attendanceAjena->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 2.0,
        'notes' => 'Intento de modificación en jornada ajena',
    ]);

    $response->assertStatus(403);

    // Verificar que NO se modificó
    $this->assertDatabaseHas('attendance_activities', [
        'id' => $activity->id,
        'hours' => 1.0,
        'notes' => 'Actividad original',
    ]);
});

test('profesor NO puede eliminar actividad en asistencia de jornada ajena mediante DELETE', function () {
    // Crear otro profesor y su jornada
    $otroProfesor = User::factory()->create(['name' => 'Prof. Torres']);
    $otroProfesor->assignRole('profesor');

    $jornadaAjena = FieldSession::factory()->create([
        'name' => 'Jornada Ajena',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $otroProfesor->id,
        'status_id' => FieldSessionStatus::where('name', 'realized')->first()->id,
    ]);

    $attendanceAjena = Attendance::factory()->create([
        'field_session_id' => $jornadaAjena->id,
        'user_id' => $this->alumno->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    $activity = AttendanceActivity::factory()->create([
        'attendance_id' => $attendanceAjena->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 1.0,
    ]);

    $this->actingAs($this->profesor);

    $response = $this->delete("/admin/attendance-activities/{$activity->id}");

    $response->assertStatus(403);

    // Verificar que NO se eliminó
    $this->assertDatabaseHas('attendance_activities', [
        'id' => $activity->id,
    ]);
});

// ============================================================================
// TESTS DE SEGURIDAD - ALUMNO (NUNCA PUEDE MODIFICAR HORAS)
// ============================================================================

test('alumno NO puede crear actividad de asistencia mediante POST directo', function () {
    $this->actingAs($this->alumno);

    $response = $this->post('/admin/attendance-activities', [
        'attendance_id' => $this->attendance->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 100, // Intentando asignarse 100 horas!
        'notes' => 'Intento de auto-asignación',
    ]);

    $response->assertStatus(403);

    // Verificar que NO se creó la actividad
    $this->assertDatabaseMissing('attendance_activities', [
        'attendance_id' => $this->attendance->id,
        'notes' => 'Intento de auto-asignación',
    ]);
});

test('alumno NO puede editar actividad de asistencia mediante PUT directo', function () {
    $activity = AttendanceActivity::factory()->create([
        'attendance_id' => $this->attendance->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 1.0,
        'notes' => 'Actividad original',
    ]);

    $this->actingAs($this->alumno);

    $response = $this->put("/admin/attendance-activities/{$activity->id}", [
        'attendance_id' => $this->attendance->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 100, // Intentando modificar a 100 horas!
        'notes' => 'Intento de modificación por alumno',
    ]);

    $response->assertStatus(403);

    // Verificar que NO se modificó
    $this->assertDatabaseHas('attendance_activities', [
        'id' => $activity->id,
        'hours' => 1.0,
        'notes' => 'Actividad original',
    ]);
});

test('alumno NO puede eliminar actividad de asistencia mediante DELETE directo', function () {
    $activity = AttendanceActivity::factory()->create([
        'attendance_id' => $this->attendance->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 1.0,
    ]);

    $this->actingAs($this->alumno);

    $response = $this->delete("/admin/attendance-activities/{$activity->id}");

    $response->assertStatus(403);

    // Verificar que NO se eliminó
    $this->assertDatabaseHas('attendance_activities', [
        'id' => $activity->id,
    ]);
});

// ============================================================================
// TESTS DE SEGURIDAD - REPRESENTANTE (NUNCA PUEDE MODIFICAR HORAS)
// ============================================================================

test('representante NO puede crear actividad de asistencia mediante POST directo', function () {
    $this->actingAs($this->representante);

    $response = $this->post('/admin/attendance-activities', [
        'attendance_id' => $this->attendance->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 1.5,
        'notes' => 'Intento de representante',
    ]);

    $response->assertStatus(403);

    // Verificar que NO se creó la actividad
    $this->assertDatabaseMissing('attendance_activities', [
        'attendance_id' => $this->attendance->id,
        'notes' => 'Intento de representante',
    ]);
});

test('representante NO puede editar actividad de asistencia mediante PUT directo', function () {
    $activity = AttendanceActivity::factory()->create([
        'attendance_id' => $this->attendance->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 1.0,
        'notes' => 'Actividad original',
    ]);

    $this->actingAs($this->representante);

    $response = $this->put("/admin/attendance-activities/{$activity->id}", [
        'attendance_id' => $this->attendance->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 2.0,
        'notes' => 'Intento de modificación por representante',
    ]);

    $response->assertStatus(403);

    // Verificar que NO se modificó
    $this->assertDatabaseHas('attendance_activities', [
        'id' => $activity->id,
        'hours' => 1.0,
        'notes' => 'Actividad original',
    ]);
});

test('representante NO puede eliminar actividad de asistencia mediante DELETE directo', function () {
    $activity = AttendanceActivity::factory()->create([
        'attendance_id' => $this->attendance->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 1.0,
    ]);

    $this->actingAs($this->representante);

    $response = $this->delete("/admin/attendance-activities/{$activity->id}");

    $response->assertStatus(403);

    // Verificar que NO se eliminó
    $this->assertDatabaseHas('attendance_activities', [
        'id' => $activity->id,
    ]);
});
