<?php

use App\Models\GradeDefinition;
use App\Models\User;
use Database\Seeders\GradeDefinitionSeeder;
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

// ─── PÁGINA DE DEFINICIONES DE GRADOS ───────────────────────────────────────

test('admin puede ver el listado de definiciones de grados', function () {
    $page = visit('/admin/grade-definitions');

    $page->assertPathIs('/admin/grade-definitions')
        ->assertSee('Definiciones de Grados')
        ->assertNoJavaScriptErrors();
});

test('admin puede ver definiciones existentes', function () {
    GradeDefinition::factory()->create(['name' => '1er Año', 'order' => 1]);
    GradeDefinition::factory()->create(['name' => '2do Año', 'order' => 2]);

    $page = visit('/admin/grade-definitions');

    $page->assertSee('1er Año')
        ->assertSee('2do Año')
        ->assertNoJavaScriptErrors();
});

// Create/edit/delete via POST/PUT/DELETE are tested in GradeDefinitionControllerTest (Feature).
// Only visit-based browser tests belong here.

// Access control, validation, and CRUD via POST/PUT/DELETE are tested in
// GradeDefinitionControllerTest (Feature). Only visit-based browser tests belong here.

// ─── CONTROL DE ACCESO ──────────────────────────────────────────────────────

test('usuario sin permiso no puede acceder a definiciones de grados', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');

    $this->actingAs($alumno);

    $page = visit('/admin/grade-definitions');

    $page->assertSee('403');
});

test('usuario sin permiso grade_definitions.create NO puede crear mediante POST', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');

    $this->actingAs($alumno);

    $response = $this->post('/admin/grade-definitions', [
        'name' => 'Grado Malicioso',
        'order' => 99,
    ]);

    $response->assertStatus(403);

    $this->assertDatabaseMissing('grade_definitions', [
        'name' => 'Grado Malicioso',
    ]);
});

test('usuario sin permiso grade_definitions.edit NO puede editar mediante PUT', function () {
    $definition = GradeDefinition::factory()->create([
        'name' => '1er Año Protegido',
        'order' => 1,
    ]);

    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');

    $this->actingAs($alumno);

    $response = $this->put("/admin/grade-definitions/{$definition->id}", [
        'name' => 'Editado por Alumno',
        'order' => 2,
    ]);

    $response->assertStatus(403);

    $this->assertDatabaseHas('grade_definitions', [
        'id' => $definition->id,
        'name' => '1er Año Protegido',
    ]);
});

test('usuario sin permiso grade_definitions.delete NO puede eliminar mediante DELETE', function () {
    $definition = GradeDefinition::factory()->create([
        'name' => 'Grado Protegido',
        'order' => 1,
    ]);

    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');

    $this->actingAs($alumno);

    $response = $this->delete("/admin/grade-definitions/{$definition->id}");

    $response->assertStatus(403);

    $this->assertDatabaseHas('grade_definitions', [
        'id' => $definition->id,
        'deleted_at' => null,
    ]);
});

// ─── VALIDACIONES ────────────────────────────────────────────────────────────

test('no se puede crear definición de grado con nombre duplicado', function () {
    $this->seed(GradeDefinitionSeeder::class);

    $this->actingAs($this->admin);

    $response = $this->post('/admin/grade-definitions', [
        'name' => '1er Año',
        'order' => 1,
    ]);

    $response->assertSessionHasErrors('name');
});

test('no se puede crear definición de grado sin campos requeridos', function () {
    $this->actingAs($this->admin);

    $response = $this->post('/admin/grade-definitions', [
        // Falta 'name' y 'order'
    ]);

    $response->assertSessionHasErrors(['name', 'order']);
});
