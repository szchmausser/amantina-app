<?php

use App\Models\HealthCondition;
use App\Models\StudentHealthRecord;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    // Crear usuarios con roles
    $this->admin = User::factory()->create(['name' => 'Admin User']);
    $this->admin->assignRole('admin');

    $this->profesor = User::factory()->create(['name' => 'Profesor User']);
    $this->profesor->assignRole('profesor');

    $this->alumno = User::factory()->create(['name' => 'Alumno User']);
    $this->alumno->assignRole('alumno');

    $this->representante = User::factory()->create(['name' => 'Representante User']);
    $this->representante->assignRole('representante');

    // Crear condición de salud
    $this->healthCondition = HealthCondition::create([
        'name' => 'Diabetes',
        'is_active' => true,
    ]);

    // Configurar storage fake para tests de archivos
    Storage::fake('public');
});

// ============================================================================
// TESTS: Admin puede gestionar student health records (CRUD completo)
// ============================================================================

test('admin can create student health record', function () {
    $this->actingAs($this->admin);

    $response = $this->post('/admin/student-health-records', [
        'user_id' => $this->alumno->id,
        'health_condition_id' => $this->healthCondition->id,
        'received_by' => $this->admin->id,
        'received_at' => '2024-09-15',
        'received_at_location' => 'Enfermería',
        'observations' => 'Paciente presenta síntomas leves',
    ]);

    $response->assertStatus(302);
    $response->assertRedirect("/admin/users/{$this->alumno->id}");
    $response->assertSessionHas('success', 'Registro de salud creado correctamente.');

    $this->assertDatabaseHas('student_health_records', [
        'user_id' => $this->alumno->id,
        'health_condition_id' => $this->healthCondition->id,
        'received_by' => $this->admin->id,
        'received_at_location' => 'Enfermería',
    ]);
});

test('admin can update student health record', function () {
    $record = StudentHealthRecord::create([
        'user_id' => $this->alumno->id,
        'health_condition_id' => $this->healthCondition->id,
        'received_by' => $this->admin->id,
        'received_at' => '2024-09-15',
        'received_at_location' => 'Enfermería',
        'observations' => 'Observación inicial',
    ]);

    $this->actingAs($this->admin);

    $response = $this->put("/admin/student-health-records/{$record->id}", [
        'health_condition_id' => $this->healthCondition->id,
        'received_by' => $this->admin->id,
        'received_at' => '2024-09-15',
        'received_at_location' => 'Consultorio Médico',
        'observations' => 'Observación actualizada',
    ]);

    $response->assertStatus(302);
    $response->assertRedirect("/admin/users/{$this->alumno->id}");
    $response->assertSessionHas('success', 'Registro de salud actualizado correctamente.');

    $this->assertDatabaseHas('student_health_records', [
        'id' => $record->id,
        'received_at_location' => 'Consultorio Médico',
        'observations' => 'Observación actualizada',
    ]);
});

test('admin can delete student health record', function () {
    $record = StudentHealthRecord::create([
        'user_id' => $this->alumno->id,
        'health_condition_id' => $this->healthCondition->id,
        'received_by' => $this->admin->id,
        'received_at' => '2024-09-15',
    ]);

    $this->actingAs($this->admin);

    $response = $this->delete("/admin/student-health-records/{$record->id}");

    $response->assertStatus(302);
    $response->assertRedirect("/admin/users/{$this->alumno->id}");
    $response->assertSessionHas('success', 'Registro de salud eliminado correctamente.');

    $this->assertSoftDeleted('student_health_records', [
        'id' => $record->id,
    ]);
});

// ============================================================================
// TESTS: Profesor NO puede gestionar student health records
// ============================================================================

test('profesor cannot create student health record', function () {
    $this->actingAs($this->profesor);

    $response = $this->post('/admin/student-health-records', [
        'user_id' => $this->alumno->id,
        'health_condition_id' => $this->healthCondition->id,
        'received_by' => $this->profesor->id,
        'received_at' => '2024-09-15',
    ]);

    $response->assertForbidden();

    $this->assertDatabaseMissing('student_health_records', [
        'user_id' => $this->alumno->id,
    ]);
});

test('profesor cannot update student health record', function () {
    $record = StudentHealthRecord::create([
        'user_id' => $this->alumno->id,
        'health_condition_id' => $this->healthCondition->id,
        'received_by' => $this->admin->id,
        'received_at' => '2024-09-15',
        'observations' => 'Original',
    ]);

    $this->actingAs($this->profesor);

    $response = $this->put("/admin/student-health-records/{$record->id}", [
        'health_condition_id' => $this->healthCondition->id,
        'received_by' => $this->profesor->id,
        'received_at' => '2024-09-15',
        'observations' => 'Modificado',
    ]);

    $response->assertForbidden();

    $this->assertDatabaseHas('student_health_records', [
        'id' => $record->id,
        'observations' => 'Original', // No cambió
    ]);
});

test('profesor cannot delete student health record', function () {
    $record = StudentHealthRecord::create([
        'user_id' => $this->alumno->id,
        'health_condition_id' => $this->healthCondition->id,
        'received_by' => $this->admin->id,
        'received_at' => '2024-09-15',
    ]);

    $this->actingAs($this->profesor);

    $response = $this->delete("/admin/student-health-records/{$record->id}");

    $response->assertForbidden();

    $this->assertDatabaseHas('student_health_records', [
        'id' => $record->id,
        'deleted_at' => null,
    ]);
});

// ============================================================================
// TESTS: Alumno NO puede acceder a student health records
// ============================================================================

test('alumno cannot create student health record', function () {
    $this->actingAs($this->alumno);

    $response = $this->post('/admin/student-health-records', [
        'user_id' => $this->alumno->id,
        'health_condition_id' => $this->healthCondition->id,
        'received_by' => $this->admin->id,
        'received_at' => '2024-09-15',
    ]);

    $response->assertForbidden();
});

test('alumno cannot update student health record', function () {
    $record = StudentHealthRecord::create([
        'user_id' => $this->alumno->id,
        'health_condition_id' => $this->healthCondition->id,
        'received_by' => $this->admin->id,
        'received_at' => '2024-09-15',
    ]);

    $this->actingAs($this->alumno);

    $response = $this->put("/admin/student-health-records/{$record->id}", [
        'health_condition_id' => $this->healthCondition->id,
        'received_by' => $this->admin->id,
        'received_at' => '2024-09-15',
    ]);

    $response->assertForbidden();
});

test('alumno cannot delete student health record', function () {
    $record = StudentHealthRecord::create([
        'user_id' => $this->alumno->id,
        'health_condition_id' => $this->healthCondition->id,
        'received_by' => $this->admin->id,
        'received_at' => '2024-09-15',
    ]);

    $this->actingAs($this->alumno);

    $response = $this->delete("/admin/student-health-records/{$record->id}");

    $response->assertForbidden();
});

// ============================================================================
// TESTS: Representante NO puede acceder a student health records
// ============================================================================

test('representante cannot create student health record', function () {
    $this->actingAs($this->representante);

    $response = $this->post('/admin/student-health-records', [
        'user_id' => $this->alumno->id,
        'health_condition_id' => $this->healthCondition->id,
        'received_by' => $this->admin->id,
        'received_at' => '2024-09-15',
    ]);

    $response->assertForbidden();
});

test('representante cannot update student health record', function () {
    $record = StudentHealthRecord::create([
        'user_id' => $this->alumno->id,
        'health_condition_id' => $this->healthCondition->id,
        'received_by' => $this->admin->id,
        'received_at' => '2024-09-15',
    ]);

    $this->actingAs($this->representante);

    $response = $this->put("/admin/student-health-records/{$record->id}", [
        'health_condition_id' => $this->healthCondition->id,
        'received_by' => $this->admin->id,
        'received_at' => '2024-09-15',
    ]);

    $response->assertForbidden();
});

test('representante cannot delete student health record', function () {
    $record = StudentHealthRecord::create([
        'user_id' => $this->alumno->id,
        'health_condition_id' => $this->healthCondition->id,
        'received_by' => $this->admin->id,
        'received_at' => '2024-09-15',
    ]);

    $this->actingAs($this->representante);

    $response = $this->delete("/admin/student-health-records/{$record->id}");

    $response->assertForbidden();
});

// ============================================================================
// TESTS: Validaciones de negocio para Student Health Records
// ============================================================================

test('admin cannot create student health record without required fields', function () {
    $this->actingAs($this->admin);

    $response = $this->post('/admin/student-health-records', [
        // Faltan campos requeridos
    ]);

    $response->assertSessionHasErrors(['user_id', 'health_condition_id', 'received_by', 'received_at']);
});

test('admin cannot create student health record with invalid user_id', function () {
    $this->actingAs($this->admin);

    $response = $this->post('/admin/student-health-records', [
        'user_id' => 99999, // ID inexistente
        'health_condition_id' => $this->healthCondition->id,
        'received_by' => $this->admin->id,
        'received_at' => '2024-09-15',
    ]);

    $response->assertSessionHasErrors('user_id');
});

test('admin cannot create student health record with invalid health_condition_id', function () {
    $this->actingAs($this->admin);

    $response = $this->post('/admin/student-health-records', [
        'user_id' => $this->alumno->id,
        'health_condition_id' => 99999, // ID inexistente
        'received_by' => $this->admin->id,
        'received_at' => '2024-09-15',
    ]);

    $response->assertSessionHasErrors('health_condition_id');
});

test('admin cannot create student health record with invalid received_by', function () {
    $this->actingAs($this->admin);

    $response = $this->post('/admin/student-health-records', [
        'user_id' => $this->alumno->id,
        'health_condition_id' => $this->healthCondition->id,
        'received_by' => 99999, // ID inexistente
        'received_at' => '2024-09-15',
    ]);

    $response->assertSessionHasErrors('received_by');
});

test('admin can create student health record with optional fields empty', function () {
    $this->actingAs($this->admin);

    $response = $this->post('/admin/student-health-records', [
        'user_id' => $this->alumno->id,
        'health_condition_id' => $this->healthCondition->id,
        'received_by' => $this->admin->id,
        'received_at' => '2024-09-15',
        // received_at_location y observations son opcionales
    ]);

    $response->assertStatus(302);
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('student_health_records', [
        'user_id' => $this->alumno->id,
        'health_condition_id' => $this->healthCondition->id,
    ]);
});

test('admin cannot create student health record with location exceeding max length', function () {
    $this->actingAs($this->admin);

    $response = $this->post('/admin/student-health-records', [
        'user_id' => $this->alumno->id,
        'health_condition_id' => $this->healthCondition->id,
        'received_by' => $this->admin->id,
        'received_at' => '2024-09-15',
        'received_at_location' => str_repeat('A', 101), // Excede 100 caracteres
    ]);

    $response->assertSessionHasErrors('received_at_location');
});
