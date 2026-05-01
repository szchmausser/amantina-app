<?php

use App\Models\AcademicYear;
use App\Models\ExternalHour;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Ejecutar seeder de roles y permisos
    $this->seed(RoleAndPermissionSeeder::class);

    // Crear año académico activo
    $this->academicYear = AcademicYear::factory()->create([
        'name' => '2025-2026',
        'is_active' => true,
    ]);

    // Crear usuarios con roles
    $this->admin = User::factory()->create(['name' => 'Admin User']);
    $this->admin->assignRole('admin');

    $this->profesor = User::factory()->create(['name' => 'Prof. García']);
    $this->profesor->assignRole('profesor');

    $this->student = User::factory()->create(['name' => 'Juan Pérez']);
    $this->student->assignRole('alumno');

    $this->representante = User::factory()->create(['name' => 'María López']);
    $this->representante->assignRole('representante');
});

// ============================================================================
// TESTS DE FUNCIONALIDAD BÁSICA (Admin)
// ============================================================================

test('admin puede crear horas externas mediante POST', function () {
    $this->actingAs($this->admin);

    $response = $this->post("/admin/users/{$this->student->id}/external-hours", [
        'user_id' => $this->student->id,
        'period' => '2020-2024',
        'hours' => 50,
        'institution_name' => 'Cruz Roja Venezolana',
        'description' => 'Horas de servicio comunitario',
    ]);

    $response->assertStatus(302); // Redirección exitosa

    // Verificar que SÍ se crearon las horas externas
    $this->assertDatabaseHas('external_hours', [
        'user_id' => $this->student->id,
        'period' => '2020-2024',
        'hours' => 50,
        'admin_id' => $this->admin->id,
    ]);
});

test('admin puede editar horas externas mediante PUT', function () {
    $externalHour = ExternalHour::factory()->create([
        'user_id' => $this->student->id,
        'admin_id' => $this->admin->id,
        'period' => '2020-2024',
        'hours' => 50,
        'institution_name' => 'Cruz Roja Venezolana',
    ]);

    $this->actingAs($this->admin);

    $response = $this->put("/admin/external-hours/{$externalHour->id}", [
        'user_id' => $this->student->id,
        'period' => '2021-2025',
        'hours' => 75,
        'institution_name' => 'Bomberos de Venezuela',
        'description' => 'Horas actualizadas',
    ]);

    $response->assertStatus(302); // Redirección exitosa

    // Verificar que SÍ se actualizaron las horas externas
    $this->assertDatabaseHas('external_hours', [
        'id' => $externalHour->id,
        'period' => '2021-2025',
        'hours' => 75,
    ]);
});

test('admin puede eliminar horas externas mediante DELETE', function () {
    $externalHour = ExternalHour::factory()->create([
        'user_id' => $this->student->id,
        'admin_id' => $this->admin->id,
    ]);

    $this->actingAs($this->admin);

    $response = $this->delete("/admin/external-hours/{$externalHour->id}");

    $response->assertStatus(302); // Redirección exitosa

    // Verificar que SÍ se eliminaron las horas externas (soft delete)
    $this->assertSoftDeleted('external_hours', [
        'id' => $externalHour->id,
    ]);
});

// ============================================================================
// TESTS DE SEGURIDAD - PROFESOR
// ============================================================================

test('profesor NO puede crear horas externas mediante POST', function () {
    $this->actingAs($this->profesor);

    $response = $this->post("/admin/users/{$this->student->id}/external-hours", [
        'user_id' => $this->student->id,
        'period' => '2020-2024',
        'hours' => 50,
        'description' => 'Intento de creación',
    ]);

    $response->assertStatus(403); // Forbidden

    // Verificar que NO se crearon las horas externas
    $this->assertDatabaseMissing('external_hours', [
        'user_id' => $this->student->id,
        'period' => '2020-2024',
    ]);
});

test('profesor NO puede editar horas externas mediante PUT', function () {
    $externalHour = ExternalHour::factory()->create([
        'user_id' => $this->student->id,
        'admin_id' => $this->admin->id,
        'period' => '2020-2024',
        'hours' => 50,
    ]);

    $this->actingAs($this->profesor);

    $response = $this->put("/admin/external-hours/{$externalHour->id}", [
        'user_id' => $this->student->id,
        'period' => '2021-2025',
        'hours' => 75,
    ]);

    $response->assertStatus(403); // Forbidden

    // Verificar que NO se actualizaron las horas externas
    $this->assertDatabaseHas('external_hours', [
        'id' => $externalHour->id,
        'period' => '2020-2024',
        'hours' => 50,
    ]);
});

test('profesor NO puede eliminar horas externas mediante DELETE', function () {
    $externalHour = ExternalHour::factory()->create([
        'user_id' => $this->student->id,
        'admin_id' => $this->admin->id,
    ]);

    $this->actingAs($this->profesor);

    $response = $this->delete("/admin/external-hours/{$externalHour->id}");

    $response->assertStatus(403); // Forbidden

    // Verificar que NO se eliminaron las horas externas
    $this->assertDatabaseHas('external_hours', [
        'id' => $externalHour->id,
        'deleted_at' => null,
    ]);
});

// ============================================================================
// TESTS DE SEGURIDAD - ALUMNO
// ============================================================================

test('alumno NO puede crear horas externas mediante POST directo', function () {
    $this->actingAs($this->student);

    $response = $this->post("/admin/users/{$this->student->id}/external-hours", [
        'user_id' => $this->student->id,
        'period' => '2020-2024',
        'hours' => 50,
        'description' => 'Intento de auto-asignación',
    ]);

    $response->assertStatus(403); // Forbidden

    // Verificar que NO se crearon las horas externas
    $this->assertDatabaseMissing('external_hours', [
        'user_id' => $this->student->id,
        'period' => '2020-2024',
    ]);
});

test('alumno NO puede editar horas externas mediante PUT directo', function () {
    $externalHour = ExternalHour::factory()->create([
        'user_id' => $this->student->id,
        'admin_id' => $this->admin->id,
        'period' => '2020-2024',
        'hours' => 50,
    ]);

    $this->actingAs($this->student);

    $response = $this->put("/admin/external-hours/{$externalHour->id}", [
        'user_id' => $this->student->id,
        'period' => '2021-2025',
        'hours' => 100,
    ]);

    $response->assertStatus(403); // Forbidden

    // Verificar que NO se actualizaron las horas externas
    $this->assertDatabaseHas('external_hours', [
        'id' => $externalHour->id,
        'hours' => 50,
    ]);
});

test('alumno NO puede eliminar horas externas mediante DELETE directo', function () {
    $externalHour = ExternalHour::factory()->create([
        'user_id' => $this->student->id,
        'admin_id' => $this->admin->id,
    ]);

    $this->actingAs($this->student);

    $response = $this->delete("/admin/external-hours/{$externalHour->id}");

    $response->assertStatus(403); // Forbidden

    // Verificar que NO se eliminaron las horas externas
    $this->assertDatabaseHas('external_hours', [
        'id' => $externalHour->id,
        'deleted_at' => null,
    ]);
});

// ============================================================================
// TESTS DE SEGURIDAD - REPRESENTANTE
// ============================================================================

test('representante NO puede crear horas externas mediante POST directo', function () {
    $this->actingAs($this->representante);

    $response = $this->post("/admin/users/{$this->student->id}/external-hours", [
        'user_id' => $this->student->id,
        'period' => '2020-2024',
        'hours' => 50,
        'description' => 'Intento de creación',
    ]);

    $response->assertStatus(403); // Forbidden

    // Verificar que NO se crearon las horas externas
    $this->assertDatabaseMissing('external_hours', [
        'user_id' => $this->student->id,
        'period' => '2020-2024',
    ]);
});

test('representante NO puede editar horas externas mediante PUT directo', function () {
    $externalHour = ExternalHour::factory()->create([
        'user_id' => $this->student->id,
        'admin_id' => $this->admin->id,
        'period' => '2020-2024',
        'hours' => 50,
    ]);

    $this->actingAs($this->representante);

    $response = $this->put("/admin/external-hours/{$externalHour->id}", [
        'user_id' => $this->student->id,
        'period' => '2021-2025',
        'hours' => 75,
    ]);

    $response->assertStatus(403); // Forbidden

    // Verificar que NO se actualizaron las horas externas
    $this->assertDatabaseHas('external_hours', [
        'id' => $externalHour->id,
        'hours' => 50,
    ]);
});

test('representante NO puede eliminar horas externas mediante DELETE directo', function () {
    $externalHour = ExternalHour::factory()->create([
        'user_id' => $this->student->id,
        'admin_id' => $this->admin->id,
    ]);

    $this->actingAs($this->representante);

    $response = $this->delete("/admin/external-hours/{$externalHour->id}");

    $response->assertStatus(403); // Forbidden

    // Verificar que NO se eliminaron las horas externas
    $this->assertDatabaseHas('external_hours', [
        'id' => $externalHour->id,
        'deleted_at' => null,
    ]);
});
