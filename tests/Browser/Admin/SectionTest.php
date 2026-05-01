<?php

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Section;
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

    // Crear año académico y grado
    $this->academicYear = AcademicYear::factory()->create([
        'name' => '2024-2025',
        'is_active' => true,
        'start_date' => '2024-09-01',
        'end_date' => '2025-07-31',
    ]);

    $this->grade = Grade::create([
        'academic_year_id' => $this->academicYear->id,
        'name' => '1er Año',
        'order' => 1,
    ]);
});

// ============================================================================
// TESTS: Admin puede gestionar sections (CRUD completo)
// ============================================================================

test('admin can view sections index', function () {
    $this->actingAs($this->admin);

    $response = $this->get('/admin/sections');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('admin/sections/index'));
});

test('admin can access create section page', function () {
    $this->actingAs($this->admin);

    $response = $this->get('/admin/sections/create');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('admin/sections/edit'));
});

test('admin can create section', function () {
    $this->actingAs($this->admin);

    $response = $this->post('/admin/sections', [
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'name' => 'A',
    ]);

    $response->assertStatus(302);
    $response->assertRedirect('/admin/sections?academic_year_id='.$this->academicYear->id.'&grade_id='.$this->grade->id);
    $response->assertSessionHas('success', 'Sección creada correctamente.');

    $this->assertDatabaseHas('sections', [
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'name' => 'A',
    ]);
});

test('admin can access edit section page', function () {
    $section = Section::create([
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'name' => 'A',
    ]);

    $this->actingAs($this->admin);

    $response = $this->get("/admin/sections/{$section->id}/edit");

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('admin/sections/edit'));
});

test('admin can update section', function () {
    $section = Section::create([
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'name' => 'A',
    ]);

    $this->actingAs($this->admin);

    $response = $this->put("/admin/sections/{$section->id}", [
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'name' => 'B',
    ]);

    $response->assertStatus(302);
    $response->assertRedirect('/admin/sections?academic_year_id='.$this->academicYear->id.'&grade_id='.$this->grade->id);
    $response->assertSessionHas('success', 'Sección actualizada correctamente.');

    $this->assertDatabaseHas('sections', [
        'id' => $section->id,
        'name' => 'B',
    ]);
});

test('admin can delete section', function () {
    $section = Section::create([
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'name' => 'A',
    ]);

    $this->actingAs($this->admin);

    $response = $this->delete("/admin/sections/{$section->id}");

    $response->assertStatus(302);
    $response->assertRedirect('/admin/sections?academic_year_id='.$this->academicYear->id.'&grade_id='.$this->grade->id);
    $response->assertSessionHas('success', 'Sección eliminada correctamente.');

    $this->assertSoftDeleted('sections', [
        'id' => $section->id,
    ]);
});

// ============================================================================
// TESTS: Profesor NO puede gestionar sections
// ============================================================================

test('profesor cannot view sections index', function () {
    $this->actingAs($this->profesor);

    $response = $this->get('/admin/sections');

    $response->assertForbidden();
});

test('profesor cannot access create section page', function () {
    $this->actingAs($this->profesor);

    $response = $this->get('/admin/sections/create');

    $response->assertForbidden();
});

test('profesor cannot create section', function () {
    $this->actingAs($this->profesor);

    $response = $this->post('/admin/sections', [
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'name' => 'A',
    ]);

    $response->assertForbidden();

    $this->assertDatabaseMissing('sections', [
        'name' => 'A',
    ]);
});

test('profesor cannot access edit section page', function () {
    $section = Section::create([
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'name' => 'A',
    ]);

    $this->actingAs($this->profesor);

    $response = $this->get("/admin/sections/{$section->id}/edit");

    $response->assertForbidden();
});

test('profesor cannot update section', function () {
    $section = Section::create([
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'name' => 'A',
    ]);

    $this->actingAs($this->profesor);

    $response = $this->put("/admin/sections/{$section->id}", [
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'name' => 'B',
    ]);

    $response->assertForbidden();

    $this->assertDatabaseHas('sections', [
        'id' => $section->id,
        'name' => 'A', // No cambió
    ]);
});

test('profesor cannot delete section', function () {
    $section = Section::create([
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'name' => 'A',
    ]);

    $this->actingAs($this->profesor);

    $response = $this->delete("/admin/sections/{$section->id}");

    $response->assertForbidden();

    $this->assertDatabaseHas('sections', [
        'id' => $section->id,
        'deleted_at' => null,
    ]);
});

// ============================================================================
// TESTS: Alumno NO puede acceder a sections
// ============================================================================

test('alumno cannot view sections index', function () {
    $this->actingAs($this->alumno);

    $response = $this->get('/admin/sections');

    $response->assertForbidden();
});

test('alumno cannot access create section page', function () {
    $this->actingAs($this->alumno);

    $response = $this->get('/admin/sections/create');

    $response->assertForbidden();
});

test('alumno cannot create section', function () {
    $this->actingAs($this->alumno);

    $response = $this->post('/admin/sections', [
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'name' => 'A',
    ]);

    $response->assertForbidden();
});

test('alumno cannot access edit section page', function () {
    $section = Section::create([
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'name' => 'A',
    ]);

    $this->actingAs($this->alumno);

    $response = $this->get("/admin/sections/{$section->id}/edit");

    $response->assertForbidden();
});

test('alumno cannot update section', function () {
    $section = Section::create([
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'name' => 'A',
    ]);

    $this->actingAs($this->alumno);

    $response = $this->put("/admin/sections/{$section->id}", [
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'name' => 'B',
    ]);

    $response->assertForbidden();
});

test('alumno cannot delete section', function () {
    $section = Section::create([
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'name' => 'A',
    ]);

    $this->actingAs($this->alumno);

    $response = $this->delete("/admin/sections/{$section->id}");

    $response->assertForbidden();
});

// ============================================================================
// TESTS: Representante NO puede acceder a sections
// ============================================================================

test('representante cannot view sections index', function () {
    $this->actingAs($this->representante);

    $response = $this->get('/admin/sections');

    $response->assertForbidden();
});

test('representante cannot access create section page', function () {
    $this->actingAs($this->representante);

    $response = $this->get('/admin/sections/create');

    $response->assertForbidden();
});

test('representante cannot create section', function () {
    $this->actingAs($this->representante);

    $response = $this->post('/admin/sections', [
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'name' => 'A',
    ]);

    $response->assertForbidden();
});

test('representante cannot access edit section page', function () {
    $section = Section::create([
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'name' => 'A',
    ]);

    $this->actingAs($this->representante);

    $response = $this->get("/admin/sections/{$section->id}/edit");

    $response->assertForbidden();
});

test('representante cannot update section', function () {
    $section = Section::create([
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'name' => 'A',
    ]);

    $this->actingAs($this->representante);

    $response = $this->put("/admin/sections/{$section->id}", [
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'name' => 'B',
    ]);

    $response->assertForbidden();
});

test('representante cannot delete section', function () {
    $section = Section::create([
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'name' => 'A',
    ]);

    $this->actingAs($this->representante);

    $response = $this->delete("/admin/sections/{$section->id}");

    $response->assertForbidden();
});

// ============================================================================
// TESTS: Validaciones de negocio para Sections
// ============================================================================

test('admin cannot create section without required fields', function () {
    $this->actingAs($this->admin);

    $response = $this->post('/admin/sections', [
        // Faltan campos requeridos
    ]);

    $response->assertSessionHasErrors(['academic_year_id', 'grade_id', 'name']);
});

test('admin cannot create duplicate section name in same grade and academic year', function () {
    // Crear primera sección
    Section::create([
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'name' => 'A',
    ]);

    $this->actingAs($this->admin);

    // Intentar crear otra sección con el mismo nombre en el mismo grado
    $response = $this->post('/admin/sections', [
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'name' => 'A', // Duplicado
    ]);

    $response->assertSessionHasErrors('name');
});

test('admin can create sections with same name in different grades', function () {
    $anotherGrade = Grade::create([
        'academic_year_id' => $this->academicYear->id,
        'name' => '2do Año',
        'order' => 2,
    ]);

    // Crear sección A en primer grado
    Section::create([
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'name' => 'A',
    ]);

    $this->actingAs($this->admin);

    // Crear sección A en segundo grado (debe permitirse)
    $response = $this->post('/admin/sections', [
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $anotherGrade->id,
        'name' => 'A', // Mismo nombre pero diferente grado
    ]);

    $response->assertStatus(302);
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('sections', [
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $anotherGrade->id,
        'name' => 'A',
    ]);
});

test('admin can create sections with same name in different academic years', function () {
    $anotherYear = AcademicYear::factory()->create([
        'name' => '2025-2026',
        'is_active' => false,
    ]);

    $anotherGrade = Grade::create([
        'academic_year_id' => $anotherYear->id,
        'name' => '1er Año',
        'order' => 1,
    ]);

    // Crear sección A en primer año académico
    Section::create([
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'name' => 'A',
    ]);

    $this->actingAs($this->admin);

    // Crear sección A en otro año académico (debe permitirse)
    $response = $this->post('/admin/sections', [
        'academic_year_id' => $anotherYear->id,
        'grade_id' => $anotherGrade->id,
        'name' => 'A', // Mismo nombre pero diferente año
    ]);

    $response->assertStatus(302);
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('sections', [
        'academic_year_id' => $anotherYear->id,
        'grade_id' => $anotherGrade->id,
        'name' => 'A',
    ]);
});

test('admin cannot create section with invalid name format', function () {
    $this->actingAs($this->admin);

    // Intentar crear sección con nombre en minúscula
    $response = $this->post('/admin/sections', [
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'name' => 'a', // Minúscula (inválido)
    ]);

    $response->assertSessionHasErrors('name');
});

test('admin cannot create section with multi-character name', function () {
    $this->actingAs($this->admin);

    // Intentar crear sección con nombre de múltiples caracteres
    $response = $this->post('/admin/sections', [
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'name' => 'AB', // Múltiples caracteres (inválido)
    ]);

    $response->assertSessionHasErrors('name');
});

test('admin cannot create section with grade from different academic year', function () {
    $anotherYear = AcademicYear::factory()->create([
        'name' => '2025-2026',
        'is_active' => false,
    ]);

    $gradeFromAnotherYear = Grade::create([
        'academic_year_id' => $anotherYear->id,
        'name' => '2do Año',
        'order' => 2,
    ]);

    $this->actingAs($this->admin);

    // Intentar crear sección con grado de otro año académico
    $response = $this->post('/admin/sections', [
        'academic_year_id' => $this->academicYear->id, // Año 2024-2025
        'grade_id' => $gradeFromAnotherYear->id, // Grado del año 2025-2026
        'name' => 'A',
    ]);

    $response->assertSessionHasErrors('grade_id');
});
