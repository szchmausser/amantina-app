<?php

use App\Models\Location;
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
// TESTS: Admin puede gestionar locations (CRUD completo)
// ============================================================================

test('admin can view locations index', function () {
    $this->actingAs($this->admin);

    $response = $this->get('/admin/locations');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('admin/locations/index'));
});

test('admin can create location', function () {
    $this->actingAs($this->admin);

    $response = $this->post('/admin/locations', [
        'name' => 'Biblioteca',
        'description' => 'Biblioteca principal del plantel',
    ]);

    $response->assertStatus(302);
    $response->assertRedirect('/admin/locations');
    $response->assertSessionHas('success', 'Ubicación creada correctamente.');

    $this->assertDatabaseHas('locations', [
        'name' => 'Biblioteca',
        'description' => 'Biblioteca principal del plantel',
    ]);
});

test('admin can update location', function () {
    $location = Location::create([
        'name' => 'Biblioteca',
        'description' => 'Descripción original',
    ]);

    $this->actingAs($this->admin);

    $response = $this->put("/admin/locations/{$location->id}", [
        'name' => 'Biblioteca Central',
        'description' => 'Descripción actualizada',
    ]);

    $response->assertStatus(302);
    $response->assertRedirect('/admin/locations');
    $response->assertSessionHas('success', 'Ubicación actualizada correctamente.');

    $this->assertDatabaseHas('locations', [
        'id' => $location->id,
        'name' => 'Biblioteca Central',
        'description' => 'Descripción actualizada',
    ]);
});

test('admin can delete location', function () {
    $location = Location::create([
        'name' => 'Biblioteca',
        'description' => 'Biblioteca principal',
    ]);

    $this->actingAs($this->admin);

    $response = $this->delete("/admin/locations/{$location->id}");

    $response->assertStatus(302);
    $response->assertRedirect('/admin/locations');
    $response->assertSessionHas('success', 'Ubicación eliminada correctamente.');

    $this->assertSoftDeleted('locations', [
        'id' => $location->id,
    ]);
});

// ============================================================================
// TESTS: Profesor puede gestionar locations (igual que admin)
// ============================================================================

test('profesor can view locations index', function () {
    $this->actingAs($this->profesor);

    $response = $this->get('/admin/locations');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('admin/locations/index'));
});

test('profesor can create location', function () {
    $this->actingAs($this->profesor);

    $response = $this->post('/admin/locations', [
        'name' => 'Laboratorio',
        'description' => 'Laboratorio de ciencias',
    ]);

    $response->assertStatus(302);
    $response->assertRedirect('/admin/locations');
    $response->assertSessionHas('success', 'Ubicación creada correctamente.');

    $this->assertDatabaseHas('locations', [
        'name' => 'Laboratorio',
    ]);
});

test('profesor can update location', function () {
    $location = Location::create([
        'name' => 'Laboratorio',
        'description' => 'Original',
    ]);

    $this->actingAs($this->profesor);

    $response = $this->put("/admin/locations/{$location->id}", [
        'name' => 'Laboratorio de Química',
        'description' => 'Modificado',
    ]);

    $response->assertStatus(302);
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('locations', [
        'id' => $location->id,
        'name' => 'Laboratorio de Química',
    ]);
});

test('profesor can delete location', function () {
    $location = Location::create([
        'name' => 'Laboratorio',
    ]);

    $this->actingAs($this->profesor);

    $response = $this->delete("/admin/locations/{$location->id}");

    $response->assertStatus(302);
    $response->assertSessionHas('success');

    $this->assertSoftDeleted('locations', [
        'id' => $location->id,
    ]);
});

// ============================================================================
// TESTS: Alumno NO puede acceder a locations
// ============================================================================

test('alumno cannot view locations index', function () {
    $this->actingAs($this->alumno);

    $response = $this->get('/admin/locations');

    $response->assertForbidden();
});

test('alumno cannot create location', function () {
    $this->actingAs($this->alumno);

    $response = $this->post('/admin/locations', [
        'name' => 'Biblioteca',
    ]);

    $response->assertForbidden();
});

test('alumno cannot update location', function () {
    $location = Location::create([
        'name' => 'Biblioteca',
    ]);

    $this->actingAs($this->alumno);

    $response = $this->put("/admin/locations/{$location->id}", [
        'name' => 'Biblioteca Central',
    ]);

    $response->assertForbidden();
});

test('alumno cannot delete location', function () {
    $location = Location::create([
        'name' => 'Biblioteca',
    ]);

    $this->actingAs($this->alumno);

    $response = $this->delete("/admin/locations/{$location->id}");

    $response->assertForbidden();
});

// ============================================================================
// TESTS: Representante NO puede acceder a locations
// ============================================================================

test('representante cannot view locations index', function () {
    $this->actingAs($this->representante);

    $response = $this->get('/admin/locations');

    $response->assertForbidden();
});

test('representante cannot create location', function () {
    $this->actingAs($this->representante);

    $response = $this->post('/admin/locations', [
        'name' => 'Biblioteca',
    ]);

    $response->assertForbidden();
});

test('representante cannot update location', function () {
    $location = Location::create([
        'name' => 'Biblioteca',
    ]);

    $this->actingAs($this->representante);

    $response = $this->put("/admin/locations/{$location->id}", [
        'name' => 'Biblioteca Central',
    ]);

    $response->assertForbidden();
});

test('representante cannot delete location', function () {
    $location = Location::create([
        'name' => 'Biblioteca',
    ]);

    $this->actingAs($this->representante);

    $response = $this->delete("/admin/locations/{$location->id}");

    $response->assertForbidden();
});

// ============================================================================
// TESTS: Validaciones de negocio para Locations
// ============================================================================

test('admin cannot create location without required fields', function () {
    $this->actingAs($this->admin);

    $response = $this->post('/admin/locations', [
        // Falta el campo 'name' requerido
    ]);

    $response->assertSessionHasErrors(['name']);
});

test('admin cannot create duplicate location name', function () {
    // Crear primera ubicación
    Location::create([
        'name' => 'Biblioteca',
    ]);

    $this->actingAs($this->admin);

    // Intentar crear otra ubicación con el mismo nombre
    $response = $this->post('/admin/locations', [
        'name' => 'Biblioteca', // Duplicado
    ]);

    $response->assertSessionHasErrors('name');
});

test('admin cannot update location with duplicate name', function () {
    // Crear dos ubicaciones
    Location::create([
        'name' => 'Biblioteca',
    ]);

    $laboratorio = Location::create([
        'name' => 'Laboratorio',
    ]);

    $this->actingAs($this->admin);

    // Intentar actualizar 'Laboratorio' con el nombre 'Biblioteca' (duplicado)
    $response = $this->put("/admin/locations/{$laboratorio->id}", [
        'name' => 'Biblioteca', // Duplicado
    ]);

    $response->assertSessionHasErrors('name');
});

test('admin cannot create location with name exceeding max length', function () {
    $this->actingAs($this->admin);

    $response = $this->post('/admin/locations', [
        'name' => str_repeat('A', 101), // Excede el máximo de 100 caracteres
    ]);

    $response->assertSessionHasErrors('name');
});

test('admin cannot create location with description exceeding max length', function () {
    $this->actingAs($this->admin);

    $response = $this->post('/admin/locations', [
        'name' => 'Biblioteca',
        'description' => str_repeat('A', 501), // Excede el máximo de 500 caracteres
    ]);

    $response->assertSessionHasErrors('description');
});

test('admin can create location without description', function () {
    $this->actingAs($this->admin);

    $response = $this->post('/admin/locations', [
        'name' => 'Biblioteca',
        // description es opcional
    ]);

    $response->assertStatus(302);
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('locations', [
        'name' => 'Biblioteca',
    ]);
});

test('admin can update location keeping same name', function () {
    $location = Location::create([
        'name' => 'Biblioteca',
        'description' => 'Descripción original',
    ]);

    $this->actingAs($this->admin);

    // Actualizar manteniendo el mismo nombre (debe permitirse)
    $response = $this->put("/admin/locations/{$location->id}", [
        'name' => 'Biblioteca', // Mismo nombre
        'description' => 'Descripción actualizada', // Solo cambiar descripción
    ]);

    $response->assertStatus(302);
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('locations', [
        'id' => $location->id,
        'name' => 'Biblioteca',
        'description' => 'Descripción actualizada',
    ]);
});
