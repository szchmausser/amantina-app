<?php

use App\Models\AcademicYear;
use App\Models\Grade;
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

    // Crear año académico
    $this->academicYear = AcademicYear::factory()->create([
        'name' => '2024-2025',
        'is_active' => true,
        'start_date' => '2024-09-01',
        'end_date' => '2025-07-31',
    ]);
});

// ============================================================================
// TESTS: Admin puede gestionar grades (CRUD completo)
// ============================================================================

test('admin can view grades index', function () {
    $this->actingAs($this->admin);

    $response = $this->get('/admin/grades');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('admin/grades/index'));
});

test('admin can access create grade page', function () {
    $this->actingAs($this->admin);

    $response = $this->get('/admin/grades/create');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('admin/grades/edit'));
});

test('admin can create grade', function () {
    $this->actingAs($this->admin);

    $response = $this->post('/admin/grades', [
        'academic_year_id' => $this->academicYear->id,
        'name' => '1er Año',
        'order' => 1,
    ]);

    $response->assertStatus(302);
    $response->assertRedirect('/admin/grades?academic_year_id='.$this->academicYear->id);
    $response->assertSessionHas('success', 'Grado creado correctamente.');

    $this->assertDatabaseHas('grades', [
        'academic_year_id' => $this->academicYear->id,
        'name' => '1er Año',
        'order' => 1,
    ]);
});

test('admin can access edit grade page', function () {
    $grade = Grade::create([
        'academic_year_id' => $this->academicYear->id,
        'name' => '1er Año',
        'order' => 1,
    ]);

    $this->actingAs($this->admin);

    $response = $this->get("/admin/grades/{$grade->id}/edit");

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('admin/grades/edit'));
});

test('admin can update grade', function () {
    $grade = Grade::create([
        'academic_year_id' => $this->academicYear->id,
        'name' => '1er Año',
        'order' => 1,
    ]);

    $this->actingAs($this->admin);

    $response = $this->put("/admin/grades/{$grade->id}", [
        'academic_year_id' => $this->academicYear->id,
        'name' => 'Primer Año',
        'order' => 1,
    ]);

    $response->assertStatus(302);
    $response->assertRedirect('/admin/grades?academic_year_id='.$this->academicYear->id);
    $response->assertSessionHas('success', 'Grado actualizado correctamente.');

    $this->assertDatabaseHas('grades', [
        'id' => $grade->id,
        'name' => 'Primer Año',
    ]);
});

test('admin can delete grade', function () {
    $grade = Grade::create([
        'academic_year_id' => $this->academicYear->id,
        'name' => '1er Año',
        'order' => 1,
    ]);

    $this->actingAs($this->admin);

    $response = $this->delete("/admin/grades/{$grade->id}");

    $response->assertStatus(302);
    $response->assertRedirect('/admin/grades?academic_year_id='.$this->academicYear->id);
    $response->assertSessionHas('success', 'Grado eliminado correctamente.');

    $this->assertSoftDeleted('grades', [
        'id' => $grade->id,
    ]);
});

// ============================================================================
// TESTS: Profesor NO puede gestionar grades
// ============================================================================

test('profesor cannot view grades index', function () {
    $this->actingAs($this->profesor);

    $response = $this->get('/admin/grades');

    $response->assertForbidden();
});

test('profesor cannot access create grade page', function () {
    $this->actingAs($this->profesor);

    $response = $this->get('/admin/grades/create');

    $response->assertForbidden();
});

test('profesor cannot create grade', function () {
    $this->actingAs($this->profesor);

    $response = $this->post('/admin/grades', [
        'academic_year_id' => $this->academicYear->id,
        'name' => '1er Año',
        'order' => 1,
    ]);

    $response->assertForbidden();

    $this->assertDatabaseMissing('grades', [
        'name' => '1er Año',
    ]);
});

test('profesor cannot access edit grade page', function () {
    $grade = Grade::create([
        'academic_year_id' => $this->academicYear->id,
        'name' => '1er Año',
        'order' => 1,
    ]);

    $this->actingAs($this->profesor);

    $response = $this->get("/admin/grades/{$grade->id}/edit");

    $response->assertForbidden();
});

test('profesor cannot update grade', function () {
    $grade = Grade::create([
        'academic_year_id' => $this->academicYear->id,
        'name' => '1er Año',
        'order' => 1,
    ]);

    $this->actingAs($this->profesor);

    $response = $this->put("/admin/grades/{$grade->id}", [
        'academic_year_id' => $this->academicYear->id,
        'name' => 'Primer Año',
        'order' => 1,
    ]);

    $response->assertForbidden();

    $this->assertDatabaseHas('grades', [
        'id' => $grade->id,
        'name' => '1er Año', // No cambió
    ]);
});

test('profesor cannot delete grade', function () {
    $grade = Grade::create([
        'academic_year_id' => $this->academicYear->id,
        'name' => '1er Año',
        'order' => 1,
    ]);

    $this->actingAs($this->profesor);

    $response = $this->delete("/admin/grades/{$grade->id}");

    $response->assertForbidden();

    $this->assertDatabaseHas('grades', [
        'id' => $grade->id,
        'deleted_at' => null,
    ]);
});

// ============================================================================
// TESTS: Alumno NO puede acceder a grades
// ============================================================================

test('alumno cannot view grades index', function () {
    $this->actingAs($this->alumno);

    $response = $this->get('/admin/grades');

    $response->assertForbidden();
});

test('alumno cannot access create grade page', function () {
    $this->actingAs($this->alumno);

    $response = $this->get('/admin/grades/create');

    $response->assertForbidden();
});

test('alumno cannot create grade', function () {
    $this->actingAs($this->alumno);

    $response = $this->post('/admin/grades', [
        'academic_year_id' => $this->academicYear->id,
        'name' => '1er Año',
        'order' => 1,
    ]);

    $response->assertForbidden();
});

test('alumno cannot access edit grade page', function () {
    $grade = Grade::create([
        'academic_year_id' => $this->academicYear->id,
        'name' => '1er Año',
        'order' => 1,
    ]);

    $this->actingAs($this->alumno);

    $response = $this->get("/admin/grades/{$grade->id}/edit");

    $response->assertForbidden();
});

test('alumno cannot update grade', function () {
    $grade = Grade::create([
        'academic_year_id' => $this->academicYear->id,
        'name' => '1er Año',
        'order' => 1,
    ]);

    $this->actingAs($this->alumno);

    $response = $this->put("/admin/grades/{$grade->id}", [
        'academic_year_id' => $this->academicYear->id,
        'name' => 'Primer Año',
        'order' => 1,
    ]);

    $response->assertForbidden();
});

test('alumno cannot delete grade', function () {
    $grade = Grade::create([
        'academic_year_id' => $this->academicYear->id,
        'name' => '1er Año',
        'order' => 1,
    ]);

    $this->actingAs($this->alumno);

    $response = $this->delete("/admin/grades/{$grade->id}");

    $response->assertForbidden();
});

// ============================================================================
// TESTS: Representante NO puede acceder a grades
// ============================================================================

test('representante cannot view grades index', function () {
    $this->actingAs($this->representante);

    $response = $this->get('/admin/grades');

    $response->assertForbidden();
});

test('representante cannot access create grade page', function () {
    $this->actingAs($this->representante);

    $response = $this->get('/admin/grades/create');

    $response->assertForbidden();
});

test('representante cannot create grade', function () {
    $this->actingAs($this->representante);

    $response = $this->post('/admin/grades', [
        'academic_year_id' => $this->academicYear->id,
        'name' => '1er Año',
        'order' => 1,
    ]);

    $response->assertForbidden();
});

test('representante cannot access edit grade page', function () {
    $grade = Grade::create([
        'academic_year_id' => $this->academicYear->id,
        'name' => '1er Año',
        'order' => 1,
    ]);

    $this->actingAs($this->representante);

    $response = $this->get("/admin/grades/{$grade->id}/edit");

    $response->assertForbidden();
});

test('representante cannot update grade', function () {
    $grade = Grade::create([
        'academic_year_id' => $this->academicYear->id,
        'name' => '1er Año',
        'order' => 1,
    ]);

    $this->actingAs($this->representante);

    $response = $this->put("/admin/grades/{$grade->id}", [
        'academic_year_id' => $this->academicYear->id,
        'name' => 'Primer Año',
        'order' => 1,
    ]);

    $response->assertForbidden();
});

test('representante cannot delete grade', function () {
    $grade = Grade::create([
        'academic_year_id' => $this->academicYear->id,
        'name' => '1er Año',
        'order' => 1,
    ]);

    $this->actingAs($this->representante);

    $response = $this->delete("/admin/grades/{$grade->id}");

    $response->assertForbidden();
});

// ============================================================================
// TESTS: Validaciones de negocio para Grades
// ============================================================================

test('admin cannot create grade without required fields', function () {
    $this->actingAs($this->admin);

    $response = $this->post('/admin/grades', [
        // Faltan campos requeridos
    ]);

    $response->assertSessionHasErrors(['academic_year_id', 'name', 'order']);
});

test('admin cannot create duplicate grade name in same academic year', function () {
    // Crear primer grado
    Grade::create([
        'academic_year_id' => $this->academicYear->id,
        'name' => '1er Año',
        'order' => 1,
    ]);

    $this->actingAs($this->admin);

    // Intentar crear otro grado con el mismo nombre
    $response = $this->post('/admin/grades', [
        'academic_year_id' => $this->academicYear->id,
        'name' => '1er Año', // Duplicado
        'order' => 2,
    ]);

    $response->assertSessionHasErrors('name');
});

test('admin cannot create duplicate grade order in same academic year', function () {
    // Crear primer grado
    Grade::create([
        'academic_year_id' => $this->academicYear->id,
        'name' => '1er Año',
        'order' => 1,
    ]);

    $this->actingAs($this->admin);

    // Intentar crear otro grado con el mismo order
    $response = $this->post('/admin/grades', [
        'academic_year_id' => $this->academicYear->id,
        'name' => '2do Año',
        'order' => 1, // Duplicado
    ]);

    $response->assertSessionHasErrors('order');
});

test('admin can create grades with same name in different academic years', function () {
    $anotherYear = AcademicYear::factory()->create([
        'name' => '2025-2026',
        'is_active' => false,
    ]);

    // Crear grado en primer año
    Grade::create([
        'academic_year_id' => $this->academicYear->id,
        'name' => '1er Año',
        'order' => 1,
    ]);

    $this->actingAs($this->admin);

    // Crear grado con mismo nombre en otro año (debe permitirse)
    $response = $this->post('/admin/grades', [
        'academic_year_id' => $anotherYear->id,
        'name' => '1er Año', // Mismo nombre pero diferente año
        'order' => 1,
    ]);

    $response->assertStatus(302);
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('grades', [
        'academic_year_id' => $anotherYear->id,
        'name' => '1er Año',
    ]);
});
