<?php

use App\Models\AcademicYear;
use App\Models\ActivityCategory;
use App\Models\Attendance;
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

// ============================================================================
// TESTS DE SEGURIDAD ROBUSTOS
// ============================================================================

test('alumno NO puede registrar asistencia mediante POST directo', function () {
    $this->actingAs($this->student);

    // Intento de POST directo para registrar asistencia
    $response = $this->post("/admin/field-sessions/{$this->fieldSession->id}/attendance", [
        'user_id' => $this->student->id,
        'attended' => true,
        'notes' => 'Intento de auto-registro',
    ]);

    $response->assertStatus(403);

    // Verificar que NO se creó la asistencia
    $this->assertDatabaseMissing('attendances', [
        'field_session_id' => $this->fieldSession->id,
        'user_id' => $this->student->id,
    ]);
});

test('alumno NO puede modificar su propia asistencia mediante PUT directo', function () {
    // Crear asistencia existente
    $attendance = Attendance::factory()->create([
        'field_session_id' => $this->fieldSession->id,
        'user_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    $this->actingAs($this->student);

    // Intento de PUT directo para modificar su asistencia
    $response = $this->put("/admin/attendance/{$attendance->id}", [
        'attended' => false, // Intentando cambiar a ausente
        'notes' => 'Modificado por alumno',
    ]);

    $response->assertStatus(403);

    // Verificar que NO se modificó
    $this->assertDatabaseHas('attendances', [
        'id' => $attendance->id,
        'attended' => true,
    ]);
});

test('alumno NO puede eliminar su propia asistencia mediante DELETE directo', function () {
    // Crear asistencia existente
    $attendance = Attendance::factory()->create([
        'field_session_id' => $this->fieldSession->id,
        'user_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    $this->actingAs($this->student);

    // Intento de DELETE directo
    $response = $this->delete("/admin/attendance/{$attendance->id}");

    $response->assertStatus(403);

    // Verificar que NO se eliminó
    $this->assertDatabaseHas('attendances', [
        'id' => $attendance->id,
    ]);
});

test('alumno NO puede asignarse horas mediante POST directo', function () {
    $this->actingAs($this->student);

    $activityCategory = ActivityCategory::factory()->create();

    // Intento de POST directo para asignarse horas
    $response = $this->post("/admin/field-sessions/{$this->fieldSession->id}/attendance/quick-assign-hours", [
        'user_id' => $this->student->id,
        'hours' => 100, // Intentando asignarse 100 horas!
        'activity_category_id' => $activityCategory->id,
    ]);

    $response->assertStatus(403);

    // Verificar que NO se creó la asistencia con horas
    $this->assertDatabaseMissing('attendances', [
        'field_session_id' => $this->fieldSession->id,
        'user_id' => $this->student->id,
    ]);
});

test('alumno NO puede modificar horas de otros estudiantes', function () {
    $otroEstudiante = User::factory()->create(['name' => 'Otro Estudiante']);
    $otroEstudiante->assignRole('alumno');

    Enrollment::factory()
        ->for($this->academicYear)
        ->for($this->grade)
        ->for($this->section)
        ->for($otroEstudiante, 'student')
        ->create();

    $attendance = Attendance::factory()->create([
        'field_session_id' => $this->fieldSession->id,
        'user_id' => $otroEstudiante->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    $this->actingAs($this->student);

    // Intento de PUT directo para modificar asistencia de otro
    $response = $this->put("/admin/attendance/{$attendance->id}", [
        'attended' => false,
        'notes' => 'Modificado por otro alumno',
    ]);

    $response->assertStatus(403);

    // Verificar que NO se modificó
    $this->assertDatabaseHas('attendances', [
        'id' => $attendance->id,
        'attended' => true,
    ]);
});

test('profesor NO puede modificar asistencias de jornadas que no son suyas', function () {
    // Crear otro profesor y su jornada
    $otroProfesor = User::factory()->create(['name' => 'Prof. García']);
    $otroProfesor->assignRole('profesor');

    $realizedStatus = FieldSessionStatus::where('name', 'realized')->first();

    $jornadaAjena = FieldSession::factory()->create([
        'name' => 'Jornada de Otro Profesor',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $otroProfesor->id,
        'status_id' => $realizedStatus->id,
    ]);

    $attendance = Attendance::factory()->create([
        'field_session_id' => $jornadaAjena->id,
        'user_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    $this->actingAs($this->profesor);

    // Intento de PUT directo para modificar asistencia de jornada ajena
    $response = $this->put("/admin/attendance/{$attendance->id}", [
        'attended' => false,
        'notes' => 'Modificado por profesor ajeno',
    ]);

    $response->assertStatus(403);

    // Verificar que NO se modificó
    $this->assertDatabaseHas('attendances', [
        'id' => $attendance->id,
        'attended' => true,
    ]);
});

test('representante NO puede acceder a la pantalla de asistencia', function () {
    $representante = User::factory()->create();
    $representante->assignRole('representante');

    $this->actingAs($representante);

    $page = visit("/admin/field-sessions/{$this->fieldSession->id}/attendance");

    $page->assertSee('403');
});

test('admin SÍ puede modificar cualquier asistencia', function () {
    $attendance = Attendance::factory()->create([
        'field_session_id' => $this->fieldSession->id,
        'user_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    $this->actingAs($this->admin);

    // Admin SÍ puede modificar
    $response = $this->put("/admin/attendance/{$attendance->id}", [
        'attended' => false,
        'notes' => 'Modificado por admin',
    ]);

    $response->assertStatus(302); // Redirección exitosa

    // Verificar que SÍ se modificó
    $this->assertDatabaseHas('attendances', [
        'id' => $attendance->id,
        'attended' => false,
        'notes' => 'Modificado por admin',
    ]);
});

test('profesor SÍ puede modificar asistencias de sus propias jornadas', function () {
    $attendance = Attendance::factory()->create([
        'field_session_id' => $this->fieldSession->id,
        'user_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    $this->actingAs($this->profesor);

    // Profesor SÍ puede modificar asistencias de su jornada
    $response = $this->put("/admin/attendance/{$attendance->id}", [
        'attended' => false,
        'notes' => 'Modificado por profesor dueño',
    ]);

    $response->assertStatus(302); // Redirección exitosa

    // Verificar que SÍ se modificó
    $this->assertDatabaseHas('attendances', [
        'id' => $attendance->id,
        'attended' => false,
        'notes' => 'Modificado por profesor dueño',
    ]);
});

test('profesor NO puede crear asistencia en jornada ajena mediante POST', function () {
    // Crear otro profesor y su jornada
    $otroProfesor = User::factory()->create(['name' => 'Prof. Sánchez']);
    $otroProfesor->assignRole('profesor');

    $jornadaAjena = FieldSession::factory()->create([
        'name' => 'Jornada de Otro Profesor',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $otroProfesor->id,
        'status_id' => FieldSessionStatus::where('name', 'realized')->first()->id,
    ]);

    $this->actingAs($this->profesor);

    // Intento de crear asistencia en jornada ajena
    $response = $this->post("/admin/field-sessions/{$jornadaAjena->id}/attendance", [
        'user_id' => $this->student->id,
        'attended' => true,
        'notes' => 'Intento de registro en jornada ajena',
    ]);

    $response->assertStatus(403);

    // Verificar que NO se creó la asistencia
    $this->assertDatabaseMissing('attendances', [
        'field_session_id' => $jornadaAjena->id,
        'user_id' => $this->student->id,
    ]);
});

test('profesor NO puede eliminar asistencia de jornada ajena mediante DELETE', function () {
    // Crear otro profesor y su jornada
    $otroProfesor = User::factory()->create(['name' => 'Prof. Torres']);
    $otroProfesor->assignRole('profesor');

    $jornadaAjena = FieldSession::factory()->create([
        'name' => 'Jornada Ajena',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $otroProfesor->id,
        'status_id' => FieldSessionStatus::where('name', 'realized')->first()->id,
    ]);

    $attendance = Attendance::factory()->create([
        'field_session_id' => $jornadaAjena->id,
        'user_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    $this->actingAs($this->profesor);

    // Intento de eliminar asistencia de jornada ajena
    $response = $this->delete("/admin/attendance/{$attendance->id}");

    $response->assertStatus(403);

    // Verificar que NO se eliminó
    $this->assertDatabaseHas('attendances', [
        'id' => $attendance->id,
    ]);
});

test('representante NO puede crear asistencia mediante POST directo', function () {
    $representante = User::factory()->create();
    $representante->assignRole('representante'); // representante NO tiene attendances.create

    $this->actingAs($representante);

    // Intento de POST directo para registrar asistencia
    $response = $this->post("/admin/field-sessions/{$this->fieldSession->id}/attendance", [
        'user_id' => $this->student->id,
        'attended' => true,
        'notes' => 'Intento de representante',
    ]);

    $response->assertStatus(403);

    // Verificar que NO se creó la asistencia
    $this->assertDatabaseMissing('attendances', [
        'field_session_id' => $this->fieldSession->id,
        'user_id' => $this->student->id,
        'notes' => 'Intento de representante',
    ]);
});

test('representante NO puede editar asistencia mediante PUT directo', function () {
    $attendance = Attendance::factory()->create([
        'field_session_id' => $this->fieldSession->id,
        'user_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    $representante = User::factory()->create();
    $representante->assignRole('representante'); // representante NO tiene attendances.edit

    $this->actingAs($representante);

    // Intento de PUT directo para modificar asistencia
    $response = $this->put("/admin/attendance/{$attendance->id}", [
        'attended' => false,
        'notes' => 'Modificado por representante',
    ]);

    $response->assertStatus(403);

    // Verificar que NO se modificó
    $this->assertDatabaseHas('attendances', [
        'id' => $attendance->id,
        'attended' => true,
    ]);
});

test('representante NO puede eliminar asistencia mediante DELETE directo', function () {
    $attendance = Attendance::factory()->create([
        'field_session_id' => $this->fieldSession->id,
        'user_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    $representante = User::factory()->create();
    $representante->assignRole('representante'); // representante NO tiene attendances.delete

    $this->actingAs($representante);

    // Intento de DELETE directo
    $response = $this->delete("/admin/attendance/{$attendance->id}");

    $response->assertStatus(403);

    // Verificar que NO se eliminó
    $this->assertDatabaseHas('attendances', [
        'id' => $attendance->id,
    ]);
});
