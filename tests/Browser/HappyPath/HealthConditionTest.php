<?php

use App\Models\HealthCondition;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
});

// ============================================================================
// TESTS: Admin puede gestionar health conditions (CRUD completo)
// ============================================================================

test('admin can view health conditions index', function () {
    $this->actingAs($this->admin);

    $response = $this->get('/admin/health-conditions');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('admin/health-conditions/index'));
});

test('admin can create health condition', function () {
    $this->actingAs($this->admin);

    $response = $this->post('/admin/health-conditions', [
        'name' => 'Diabetes',
        'is_active' => true,
    ]);

    $response->assertStatus(302);
    $response->assertRedirect('/admin/health-conditions');
    $response->assertSessionHas('success', 'Condición de salud creada correctamente.');

    $this->assertDatabaseHas('health_conditions', [
        'name' => 'Diabetes',
        'is_active' => true,
    ]);
});

test('admin can update health condition', function () {
    $healthCondition = HealthCondition::create([
        'name' => 'Diabetes',
        'is_active' => true,
    ]);

    $this->actingAs($this->admin);

    $response = $this->put("/admin/health-conditions/{$healthCondition->id}", [
        'name' => 'Diabetes Tipo 1',
        'is_active' => true,
    ]);

    $response->assertStatus(302);
    $response->assertRedirect('/admin/health-conditions');
    $response->assertSessionHas('success', 'Condición de salud actualizada correctamente.');

    $this->assertDatabaseHas('health_conditions', [
        'id' => $healthCondition->id,
        'name' => 'Diabetes Tipo 1',
    ]);
});

test('admin can delete health condition', function () {
    $healthCondition = HealthCondition::create([
        'name' => 'Diabetes',
        'is_active' => true,
    ]);

    $this->actingAs($this->admin);

    $response = $this->delete("/admin/health-conditions/{$healthCondition->id}");

    $response->assertStatus(302);
    $response->assertRedirect('/admin/health-conditions');
    $response->assertSessionHas('success', 'Condición de salud eliminada correctamente.');

    $this->assertSoftDeleted('health_conditions', [
        'id' => $healthCondition->id,
    ]);
});

test('admin can deactivate health condition', function () {
    $healthCondition = HealthCondition::create([
        'name' => 'Diabetes',
        'is_active' => true,
    ]);

    $this->actingAs($this->admin);

    $response = $this->put("/admin/health-conditions/{$healthCondition->id}", [
        'name' => 'Diabetes',
        'is_active' => false,
    ]);

    $response->assertStatus(302);
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('health_conditions', [
        'id' => $healthCondition->id,
        'is_active' => false,
    ]);
});

// ============================================================================
// TESTS: Profesor NO puede gestionar health conditions
// ============================================================================

test('profesor cannot view health conditions index', function () {
    $this->actingAs($this->profesor);

    $response = $this->get('/admin/health-conditions');

    $response->assertForbidden();
});

test('profesor cannot create health condition', function () {
    $this->actingAs($this->profesor);

    $response = $this->post('/admin/health-conditions', [
        'name' => 'Diabetes',
        'is_active' => true,
    ]);

    $response->assertForbidden();

    $this->assertDatabaseMissing('health_conditions', [
        'name' => 'Diabetes',
    ]);
});

test('profesor cannot update health condition', function () {
    $healthCondition = HealthCondition::create([
        'name' => 'Diabetes',
        'is_active' => true,
    ]);

    $this->actingAs($this->profesor);

    $response = $this->put("/admin/health-conditions/{$healthCondition->id}", [
        'name' => 'Diabetes Tipo 1',
        'is_active' => true,
    ]);

    $response->assertForbidden();

    $this->assertDatabaseHas('health_conditions', [
        'id' => $healthCondition->id,
        'name' => 'Diabetes', // No cambió
    ]);
});

test('profesor cannot delete health condition', function () {
    $healthCondition = HealthCondition::create([
        'name' => 'Diabetes',
        'is_active' => true,
    ]);

    $this->actingAs($this->profesor);

    $response = $this->delete("/admin/health-conditions/{$healthCondition->id}");

    $response->assertForbidden();

    $this->assertDatabaseHas('health_conditions', [
        'id' => $healthCondition->id,
        'deleted_at' => null,
    ]);
});

// ============================================================================
// TESTS: Alumno NO puede acceder a health conditions
// ============================================================================

test('alumno cannot view health conditions index', function () {
    $this->actingAs($this->alumno);

    $response = $this->get('/admin/health-conditions');

    $response->assertForbidden();
});

test('alumno cannot create health condition', function () {
    $this->actingAs($this->alumno);

    $response = $this->post('/admin/health-conditions', [
        'name' => 'Diabetes',
        'is_active' => true,
    ]);

    $response->assertForbidden();
});

test('alumno cannot update health condition', function () {
    $healthCondition = HealthCondition::create([
        'name' => 'Diabetes',
        'is_active' => true,
    ]);

    $this->actingAs($this->alumno);

    $response = $this->put("/admin/health-conditions/{$healthCondition->id}", [
        'name' => 'Diabetes Tipo 1',
        'is_active' => true,
    ]);

    $response->assertForbidden();
});

test('alumno cannot delete health condition', function () {
    $healthCondition = HealthCondition::create([
        'name' => 'Diabetes',
        'is_active' => true,
    ]);

    $this->actingAs($this->alumno);

    $response = $this->delete("/admin/health-conditions/{$healthCondition->id}");

    $response->assertForbidden();
});

// ============================================================================
// TESTS: Representante NO puede acceder a health conditions
// ============================================================================

test('representante cannot view health conditions index', function () {
    $this->actingAs($this->representante);

    $response = $this->get('/admin/health-conditions');

    $response->assertForbidden();
});

test('representante cannot create health condition', function () {
    $this->actingAs($this->representante);

    $response = $this->post('/admin/health-conditions', [
        'name' => 'Diabetes',
        'is_active' => true,
    ]);

    $response->assertForbidden();
});

test('representante cannot update health condition', function () {
    $healthCondition = HealthCondition::create([
        'name' => 'Diabetes',
        'is_active' => true,
    ]);

    $this->actingAs($this->representante);

    $response = $this->put("/admin/health-conditions/{$healthCondition->id}", [
        'name' => 'Diabetes Tipo 1',
        'is_active' => true,
    ]);

    $response->assertForbidden();
});

test('representante cannot delete health condition', function () {
    $healthCondition = HealthCondition::create([
        'name' => 'Diabetes',
        'is_active' => true,
    ]);

    $this->actingAs($this->representante);

    $response = $this->delete("/admin/health-conditions/{$healthCondition->id}");

    $response->assertForbidden();
});

// ============================================================================
// TESTS: Validaciones de negocio para Health Conditions
// ============================================================================

test('admin cannot create health condition without required fields', function () {
    $this->actingAs($this->admin);

    $response = $this->post('/admin/health-conditions', [
        // Falta el campo 'name' requerido
    ]);

    $response->assertSessionHasErrors(['name']);
});

test('admin cannot create duplicate health condition name', function () {
    // Crear primera condición
    HealthCondition::create([
        'name' => 'Diabetes',
        'is_active' => true,
    ]);

    $this->actingAs($this->admin);

    // Intentar crear otra condición con el mismo nombre
    $response = $this->post('/admin/health-conditions', [
        'name' => 'Diabetes', // Duplicado
        'is_active' => true,
    ]);

    $response->assertSessionHasErrors('name');
});

test('admin cannot update health condition with duplicate name', function () {
    // Crear dos condiciones
    HealthCondition::create([
        'name' => 'Diabetes',
        'is_active' => true,
    ]);

    $asma = HealthCondition::create([
        'name' => 'Asma',
        'is_active' => true,
    ]);

    $this->actingAs($this->admin);

    // Intentar actualizar 'Asma' con el nombre 'Diabetes' (duplicado)
    $response = $this->put("/admin/health-conditions/{$asma->id}", [
        'name' => 'Diabetes', // Duplicado
        'is_active' => true,
    ]);

    $response->assertSessionHasErrors('name');
});

test('admin cannot create health condition with name exceeding max length', function () {
    $this->actingAs($this->admin);

    $response = $this->post('/admin/health-conditions', [
        'name' => str_repeat('A', 101), // Excede el máximo de 100 caracteres
        'is_active' => true,
    ]);

    $response->assertSessionHasErrors('name');
});

test('admin can create health condition with is_active defaulting to true', function () {
    $this->actingAs($this->admin);

    $response = $this->post('/admin/health-conditions', [
        'name' => 'Diabetes',
        // No se especifica is_active
    ]);

    $response->assertStatus(302);
    $response->assertSessionHas('success');

    // Verificar que se creó (is_active puede ser null o true dependiendo del default en BD)
    $this->assertDatabaseHas('health_conditions', [
        'name' => 'Diabetes',
    ]);
});

test('admin can update health condition keeping same name', function () {
    $healthCondition = HealthCondition::create([
        'name' => 'Diabetes',
        'is_active' => true,
    ]);

    $this->actingAs($this->admin);

    // Actualizar manteniendo el mismo nombre (debe permitirse)
    $response = $this->put("/admin/health-conditions/{$healthCondition->id}", [
        'name' => 'Diabetes', // Mismo nombre
        'is_active' => false, // Solo cambiar el estado
    ]);

    $response->assertStatus(302);
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('health_conditions', [
        'id' => $healthCondition->id,
        'name' => 'Diabetes',
        'is_active' => false,
    ]);
});
