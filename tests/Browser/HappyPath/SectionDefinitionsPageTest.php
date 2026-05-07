<?php

use App\Models\SectionDefinition;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

beforeEach(function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);
    $this->seed(RoleAndPermissionSeeder::class);

    $this->admin = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
    ]);
    $this->admin->assignRole('admin');

    $this->actingAs($this->admin);
});

// ─── PÁGINA DE DEFINICIONES DE SECCIONES ────────────────────────────────────

test('admin puede ver el listado de definiciones de secciones', function () {
    $page = visit('/admin/section-definitions');

    $page->assertPathIs('/admin/section-definitions')
        ->assertSee('Definiciones de Secciones')
        ->assertNoJavaScriptErrors();
});

test('admin puede ver definiciones de secciones existentes', function () {
    SectionDefinition::factory()->create(['name' => 'A']);
    SectionDefinition::factory()->create(['name' => 'B']);

    $page = visit('/admin/section-definitions');

    $page->assertSee('A')
        ->assertSee('B')
        ->assertNoJavaScriptErrors();
});

test('admin puede crear una definición de sección mediante POST', function () {
    $this->actingAs($this->admin);

    $response = $this->post('/admin/section-definitions', [
        'name' => 'A',
    ]);

    $response->assertStatus(302);
    $response->assertSessionHas('success', 'Definición de sección creada correctamente.');

    $this->assertDatabaseHas('section_definitions', [
        'name' => 'A',
    ]);
});

test('admin puede editar una definición de sección mediante PUT', function () {
    $definition = SectionDefinition::factory()->create(['name' => 'A']);

    $this->actingAs($this->admin);

    $response = $this->put("/admin/section-definitions/{$definition->id}", [
        'name' => 'B',
    ]);

    $response->assertStatus(302);
    $response->assertSessionHas('success', 'Definición de sección actualizada correctamente.');

    $this->assertDatabaseHas('section_definitions', [
        'id' => $definition->id,
        'name' => 'B',
    ]);
});

test('admin puede eliminar (soft delete) una definición de sección mediante DELETE', function () {
    $definition = SectionDefinition::factory()->create(['name' => 'A']);

    $this->actingAs($this->admin);

    $response = $this->delete("/admin/section-definitions/{$definition->id}");

    $response->assertStatus(302);
    $response->assertSessionHas('success', 'Definición de sección eliminada correctamente.');

    $this->assertSoftDeleted('section_definitions', [
        'id' => $definition->id,
    ]);
});

// ─── CONTROL DE ACCESO ──────────────────────────────────────────────────────

test('usuario sin permiso no puede acceder a definiciones de secciones', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');

    $this->actingAs($alumno);

    $page = visit('/admin/section-definitions');

    $page->assertSee('403');
});

test('usuario sin permiso section_definitions.create NO puede crear mediante POST', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');

    $this->actingAs($alumno);

    $response = $this->post('/admin/section-definitions', [
        'name' => 'A',
    ]);

    $response->assertStatus(403);

    $this->assertDatabaseMissing('section_definitions', [
        'name' => 'A',
    ]);
});

test('usuario sin permiso section_definitions.edit NO puede editar mediante PUT', function () {
    $definition = SectionDefinition::factory()->create(['name' => 'A']);

    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');

    $this->actingAs($alumno);

    $response = $this->put("/admin/section-definitions/{$definition->id}", [
        'name' => 'B',
    ]);

    $response->assertStatus(403);

    $this->assertDatabaseHas('section_definitions', [
        'id' => $definition->id,
        'name' => 'A',
    ]);
});

test('usuario sin permiso section_definitions.delete NO puede eliminar mediante DELETE', function () {
    $definition = SectionDefinition::factory()->create(['name' => 'A']);

    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');

    $this->actingAs($alumno);

    $response = $this->delete("/admin/section-definitions/{$definition->id}");

    $response->assertStatus(403);

    $this->assertDatabaseHas('section_definitions', [
        'id' => $definition->id,
        'deleted_at' => null,
    ]);
});

// ─── VALIDACIONES ────────────────────────────────────────────────────────────

test('no se puede crear definición de sección con nombre duplicado', function () {
    SectionDefinition::factory()->create(['name' => 'A']);

    $this->actingAs($this->admin);

    $response = $this->post('/admin/section-definitions', [
        'name' => 'A',
    ]);

    $response->assertSessionHasErrors('name');
});

test('no se puede crear definición de sección con nombre inválido (no letra)', function () {
    $this->actingAs($this->admin);

    // Número
    $response = $this->post('/admin/section-definitions', [
        'name' => '1',
    ]);
    $response->assertSessionHasErrors('name');

    // Múltiples caracteres
    $response = $this->post('/admin/section-definitions', [
        'name' => 'AA',
    ]);
    $response->assertSessionHasErrors('name');

    // Minúscula
    $response = $this->post('/admin/section-definitions', [
        'name' => 'a',
    ]);
    $response->assertSessionHasErrors('name');
});

test('no se puede crear definición de sección sin campos requeridos', function () {
    $this->actingAs($this->admin);

    $response = $this->post('/admin/section-definitions', [
        // Falta 'name'
    ]);

    $response->assertSessionHasErrors(['name']);
});
