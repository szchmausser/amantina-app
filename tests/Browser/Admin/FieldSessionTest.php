<?php

use App\Models\AcademicYear;
use App\Models\FieldSession;
use App\Models\FieldSessionStatus;
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

    $this->profesor = User::factory()->create(['name' => 'Prof. García']);
    $this->profesor->assignRole('profesor');

    $this->actingAs($this->admin);

    $this->academicYear = AcademicYear::factory()->create([
        'name' => '2024-2025',
        'is_active' => true,
    ]);

    $this->plannedStatus = FieldSessionStatus::where('name', 'planned')->first();
    $this->realizedStatus = FieldSessionStatus::where('name', 'realized')->first();
});

test('admin puede ver el listado de jornadas de campo', function () {
    $page = visit('/admin/field-sessions');

    $page->assertPathIs('/admin/field-sessions')
        ->assertSee('Jornadas')
        ->assertNoJavaScriptErrors();
});

test('admin puede ver jornadas existentes en el listado', function () {
    FieldSession::factory()->create([
        'name' => 'Siembra en Huerto Escolar',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $this->profesor->id,
        'status_id' => $this->plannedStatus->id,
    ]);

    $page = visit('/admin/field-sessions');

    $page->assertSee('Siembra en Huerto Escolar')
        ->assertNoJavaScriptErrors();
});

test('admin puede acceder al formulario de creación de jornada', function () {
    $page = visit('/admin/field-sessions/create');

    $page->assertPathIs('/admin/field-sessions/create')
        ->assertNoJavaScriptErrors();
});

test('admin puede ver el detalle de una jornada de campo', function () {
    $session = FieldSession::factory()->create([
        'name' => 'Cosecha de Tomates',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $this->profesor->id,
        'status_id' => $this->plannedStatus->id,
        'location_name' => 'Huerto escolar',
        'activity_name' => 'Cosecha',
    ]);

    $page = visit("/admin/field-sessions/{$session->id}");

    $page->assertSee('Cosecha de Tomates')
        ->assertNoJavaScriptErrors();
});

test('admin puede acceder al formulario de edición de jornada', function () {
    $session = FieldSession::factory()->create([
        'name' => 'Riego de Plantas',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $this->profesor->id,
        'status_id' => $this->plannedStatus->id,
    ]);

    $page = visit("/admin/field-sessions/{$session->id}/edit");

    // Verificar que la página carga (sin verificar contenido específico por ahora)
    $page->assertPathIs("/admin/field-sessions/{$session->id}/edit")
        ->assertNoJavaScriptErrors();
});

test('profesor puede ver sus propias jornadas', function () {
    $this->actingAs($this->profesor);

    FieldSession::factory()->create([
        'name' => 'Jornada del Profesor',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $this->profesor->id,
        'status_id' => $this->plannedStatus->id,
    ]);

    $page = visit('/admin/field-sessions');

    $page->assertSee('Jornada del Profesor')
        ->assertNoJavaScriptErrors();
});

test('admin puede filtrar jornadas por estado', function () {
    FieldSession::factory()->create([
        'name' => 'Jornada Planificada',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $this->profesor->id,
        'status_id' => $this->plannedStatus->id,
    ]);

    FieldSession::factory()->create([
        'name' => 'Jornada Realizada',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $this->profesor->id,
        'status_id' => $this->realizedStatus->id,
    ]);

    $page = visit('/admin/field-sessions?status='.$this->plannedStatus->id);

    $page->assertSee('Jornada Planificada')
        ->assertNoJavaScriptErrors();
});

// ============================================================================
// TESTS DE SEGURIDAD BASADOS EN PERMISOS
// ============================================================================

test('usuario sin permiso field_sessions.view NO puede ver listado (alumno)', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno'); // alumno NO tiene field_sessions.view

    $this->actingAs($alumno);

    $page = visit('/admin/field-sessions');

    $page->assertSee('403');
});

test('usuario sin permiso field_sessions.view NO puede ver detalle (alumno)', function () {
    $session = FieldSession::factory()->create([
        'name' => 'Jornada Privada',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $this->profesor->id,
        'status_id' => $this->plannedStatus->id,
    ]);

    $alumno = User::factory()->create();
    $alumno->assignRole('alumno'); // alumno NO tiene field_sessions.view

    $this->actingAs($alumno);

    $page = visit("/admin/field-sessions/{$session->id}");

    $page->assertSee('403');
});

test('usuario sin permiso field_sessions.create NO puede acceder a formulario de creación (alumno)', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno'); // alumno NO tiene field_sessions.create

    $this->actingAs($alumno);

    $page = visit('/admin/field-sessions/create');

    $page->assertSee('403');
});

test('usuario sin permiso field_sessions.create NO puede crear jornada mediante POST (alumno)', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno'); // alumno NO tiene field_sessions.create

    $this->actingAs($alumno);

    $response = $this->post('/admin/field-sessions', [
        'name' => 'Jornada Maliciosa',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $alumno->id,
        'status_id' => $this->plannedStatus->id,
        'start_datetime' => now()->addDay(),
        'end_datetime' => now()->addDay()->addHours(2),
        'base_hours' => 2.0,
    ]);

    $response->assertStatus(403);

    // Verificar que NO se creó la jornada
    $this->assertDatabaseMissing('field_sessions', [
        'name' => 'Jornada Maliciosa',
    ]);
});

test('usuario CON permiso field_sessions.create SÍ puede crear jornada (profesor)', function () {
    $this->actingAs($this->profesor); // profesor SÍ tiene field_sessions.create

    $response = $this->post('/admin/field-sessions', [
        'name' => 'Jornada del Profesor',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $this->profesor->id,
        'status_id' => $this->plannedStatus->id,
        'start_datetime' => now()->addDay()->format('Y-m-d H:i:s'),
        'end_datetime' => now()->addDay()->addHours(2)->format('Y-m-d H:i:s'),
        'base_hours' => 2.0,
        'location_name' => 'Huerto',
        'activity_name' => 'Siembra',
    ]);

    $response->assertStatus(302); // Redirección exitosa

    // Verificar que SÍ se creó la jornada
    $this->assertDatabaseHas('field_sessions', [
        'name' => 'Jornada del Profesor',
        'user_id' => $this->profesor->id,
    ]);
});

test('profesor NO puede editar jornada ajena mediante PUT', function () {
    $otroProfesor = User::factory()->create(['name' => 'Prof. López']);
    $otroProfesor->assignRole('profesor');

    $jornadaAjena = FieldSession::factory()->create([
        'name' => 'Jornada de Otro Profesor',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $otroProfesor->id,
        'status_id' => $this->plannedStatus->id,
    ]);

    $this->actingAs($this->profesor);

    // Intento de editar jornada ajena
    $response = $this->put("/admin/field-sessions/{$jornadaAjena->id}", [
        'name' => 'Jornada Modificada Maliciosamente',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $otroProfesor->id,
        'status_id' => $this->plannedStatus->id,
        'start_datetime' => now()->addDay()->format('Y-m-d H:i:s'),
        'end_datetime' => now()->addDay()->addHours(2)->format('Y-m-d H:i:s'),
        'base_hours' => 2.0,
    ]);

    $response->assertStatus(403);

    // Verificar que NO se modificó
    $this->assertDatabaseHas('field_sessions', [
        'id' => $jornadaAjena->id,
        'name' => 'Jornada de Otro Profesor',
    ]);
});

test('profesor SÍ puede editar su propia jornada mediante PUT', function () {
    $jornadaPropia = FieldSession::factory()->create([
        'name' => 'Mi Jornada',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $this->profesor->id,
        'status_id' => $this->plannedStatus->id,
    ]);

    $this->actingAs($this->profesor);

    // Editar su propia jornada
    $response = $this->put("/admin/field-sessions/{$jornadaPropia->id}", [
        'name' => 'Mi Jornada Actualizada',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $this->profesor->id,
        'status_id' => $this->realizedStatus->id,
        'start_datetime' => now()->addDay()->format('Y-m-d H:i:s'),
        'end_datetime' => now()->addDay()->addHours(2)->format('Y-m-d H:i:s'),
        'base_hours' => 2.0,
        'location_name' => 'Huerto',
        'activity_name' => 'Siembra',
    ]);

    $response->assertStatus(302); // Redirección exitosa

    // Verificar que SÍ se modificó
    $this->assertDatabaseHas('field_sessions', [
        'id' => $jornadaPropia->id,
        'name' => 'Mi Jornada Actualizada',
    ]);
});

test('profesor NO puede eliminar jornada ajena mediante DELETE', function () {
    $otroProfesor = User::factory()->create(['name' => 'Prof. Ramírez']);
    $otroProfesor->assignRole('profesor');

    $jornadaAjena = FieldSession::factory()->create([
        'name' => 'Jornada Ajena',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $otroProfesor->id,
        'status_id' => $this->plannedStatus->id,
    ]);

    $this->actingAs($this->profesor);

    $response = $this->delete("/admin/field-sessions/{$jornadaAjena->id}");

    $response->assertStatus(403);

    // Verificar que NO se eliminó
    $this->assertDatabaseHas('field_sessions', [
        'id' => $jornadaAjena->id,
    ]);
});

test('profesor SÍ puede eliminar su propia jornada mediante DELETE', function () {
    $jornadaPropia = FieldSession::factory()->create([
        'name' => 'Jornada a Eliminar',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $this->profesor->id,
        'status_id' => $this->plannedStatus->id,
    ]);

    $this->actingAs($this->profesor);

    $response = $this->delete("/admin/field-sessions/{$jornadaPropia->id}");

    $response->assertStatus(302); // Redirección exitosa

    // Verificar que SÍ se eliminó (soft delete)
    $this->assertSoftDeleted('field_sessions', [
        'id' => $jornadaPropia->id,
    ]);
});

test('usuario sin permiso field_sessions.edit NO puede editar jornada mediante PUT (alumno)', function () {
    $session = FieldSession::factory()->create([
        'name' => 'Jornada Original',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $this->profesor->id,
        'status_id' => $this->plannedStatus->id,
    ]);

    $alumno = User::factory()->create();
    $alumno->assignRole('alumno'); // alumno NO tiene field_sessions.edit

    $this->actingAs($alumno);

    $response = $this->put("/admin/field-sessions/{$session->id}", [
        'name' => 'Jornada Modificada por Alumno',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $this->profesor->id,
        'status_id' => $this->plannedStatus->id,
        'start_datetime' => now()->addDay()->format('Y-m-d H:i:s'),
        'end_datetime' => now()->addDay()->addHours(2)->format('Y-m-d H:i:s'),
        'base_hours' => 2.0,
    ]);

    $response->assertStatus(403);

    // Verificar que NO se modificó
    $this->assertDatabaseHas('field_sessions', [
        'id' => $session->id,
        'name' => 'Jornada Original',
    ]);
});

test('usuario sin permiso field_sessions.delete NO puede eliminar jornada mediante DELETE (alumno)', function () {
    $session = FieldSession::factory()->create([
        'name' => 'Jornada a Proteger',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $this->profesor->id,
        'status_id' => $this->plannedStatus->id,
    ]);

    $alumno = User::factory()->create();
    $alumno->assignRole('alumno'); // alumno NO tiene field_sessions.delete

    $this->actingAs($alumno);

    $response = $this->delete("/admin/field-sessions/{$session->id}");

    $response->assertStatus(403);

    // Verificar que NO se eliminó
    $this->assertDatabaseHas('field_sessions', [
        'id' => $session->id,
    ]);
});

test('usuario sin permiso field_sessions.view NO puede acceder a ninguna ruta (representante)', function () {
    $representante = User::factory()->create();
    $representante->assignRole('representante'); // representante NO tiene field_sessions.view

    $this->actingAs($representante);

    $page = visit('/admin/field-sessions');

    $page->assertSee('403');
});

// ============================================================================
// TESTS POSITIVOS DE ADMIN (COMPLETAR COBERTURA 100%)
// ============================================================================

test('usuario CON permiso field_sessions.create SÍ puede crear jornada mediante POST (admin)', function () {
    $this->actingAs($this->admin); // admin SÍ tiene field_sessions.create

    $response = $this->post('/admin/field-sessions', [
        'name' => 'Jornada Creada por Admin',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $this->profesor->id,
        'status_id' => $this->plannedStatus->id,
        'start_datetime' => now()->addDay()->format('Y-m-d H:i:s'),
        'end_datetime' => now()->addDay()->addHours(2)->format('Y-m-d H:i:s'),
        'base_hours' => 2.0,
        'location_name' => 'Huerto Escolar',
        'activity_name' => 'Siembra',
    ]);

    $response->assertStatus(302); // Redirección exitosa

    // Verificar que SÍ se creó la jornada
    $this->assertDatabaseHas('field_sessions', [
        'name' => 'Jornada Creada por Admin',
        'user_id' => $this->profesor->id,
    ]);
});

test('usuario CON permiso field_sessions.edit SÍ puede editar cualquier jornada mediante PUT (admin)', function () {
    $jornadaDeOtroProfesor = FieldSession::factory()->create([
        'name' => 'Jornada Original',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $this->profesor->id,
        'status_id' => $this->plannedStatus->id,
    ]);

    $this->actingAs($this->admin); // admin SÍ tiene field_sessions.edit sin restricciones

    // Admin puede editar jornada de cualquier profesor
    $response = $this->put("/admin/field-sessions/{$jornadaDeOtroProfesor->id}", [
        'name' => 'Jornada Editada por Admin',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $this->profesor->id,
        'status_id' => $this->realizedStatus->id,
        'start_datetime' => now()->addDay()->format('Y-m-d H:i:s'),
        'end_datetime' => now()->addDay()->addHours(2)->format('Y-m-d H:i:s'),
        'base_hours' => 2.0,
        'location_name' => 'Huerto',
        'activity_name' => 'Cosecha',
    ]);

    $response->assertStatus(302); // Redirección exitosa

    // Verificar que SÍ se modificó
    $this->assertDatabaseHas('field_sessions', [
        'id' => $jornadaDeOtroProfesor->id,
        'name' => 'Jornada Editada por Admin',
        'status_id' => $this->realizedStatus->id,
    ]);
});

test('usuario CON permiso field_sessions.delete SÍ puede eliminar cualquier jornada mediante DELETE (admin)', function () {
    $jornadaDeOtroProfesor = FieldSession::factory()->create([
        'name' => 'Jornada a Eliminar',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $this->profesor->id,
        'status_id' => $this->plannedStatus->id,
    ]);

    $this->actingAs($this->admin); // admin SÍ tiene field_sessions.delete sin restricciones

    // Admin puede eliminar jornada de cualquier profesor
    $response = $this->delete("/admin/field-sessions/{$jornadaDeOtroProfesor->id}");

    $response->assertStatus(302); // Redirección exitosa

    // Verificar que SÍ se eliminó (soft delete)
    $this->assertSoftDeleted('field_sessions', [
        'id' => $jornadaDeOtroProfesor->id,
    ]);
});
