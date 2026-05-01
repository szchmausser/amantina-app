<?php

use App\Models\AcademicYear;
use App\Models\SchoolTerm;
use App\Models\TermType;
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

    // Crear TermType
    $this->termType = TermType::firstOrCreate(
        ['name' => 'Lapso 1'],
        ['order' => 1, 'is_active' => true]
    );

    $this->termType2 = TermType::firstOrCreate(
        ['name' => 'Lapso 2'],
        ['order' => 2, 'is_active' => true]
    );
});

// ============================================================================
// TESTS: Admin puede gestionar school terms (CRUD completo)
// ============================================================================

test('admin can view school terms index', function () {
    $this->actingAs($this->admin);

    $response = $this->get('/admin/school-terms');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('admin/school-terms/index'));
});

test('admin can access create school term page', function () {
    $this->actingAs($this->admin);

    $response = $this->get('/admin/school-terms/create');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('admin/school-terms/edit'));
});

test('admin can create school term', function () {
    $this->actingAs($this->admin);

    $response = $this->post('/admin/school-terms', [
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType->id,
        'term_type_name' => $this->termType->name,
        'start_date' => '2024-09-01',
        'end_date' => '2024-11-30',
    ]);

    $response->assertStatus(302);
    $response->assertRedirect('/admin/school-terms?academic_year_id='.$this->academicYear->id);
    $response->assertSessionHas('success', 'Lapso académico creado correctamente.');

    $this->assertDatabaseHas('school_terms', [
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType->id,
        'term_type_name' => $this->termType->name,
        'start_date' => '2024-09-01',
        'end_date' => '2024-11-30',
    ]);
});

test('admin can access edit school term page', function () {
    $schoolTerm = SchoolTerm::create([
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType->id,
        'term_type_name' => $this->termType->name,
        'start_date' => '2024-09-01',
        'end_date' => '2024-11-30',
    ]);

    $this->actingAs($this->admin);

    $response = $this->get("/admin/school-terms/{$schoolTerm->id}/edit");

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('admin/school-terms/edit'));
});

test('admin can update school term', function () {
    $schoolTerm = SchoolTerm::create([
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType->id,
        'term_type_name' => $this->termType->name,
        'start_date' => '2024-09-01',
        'end_date' => '2024-11-30',
    ]);

    $this->actingAs($this->admin);

    $response = $this->put("/admin/school-terms/{$schoolTerm->id}", [
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType->id,
        'term_type_name' => $this->termType->name,
        'start_date' => '2024-09-01',
        'end_date' => '2024-12-15', // Fecha actualizada
    ]);

    $response->assertStatus(302);
    $response->assertRedirect('/admin/school-terms?academic_year_id='.$this->academicYear->id);
    $response->assertSessionHas('success', 'Lapso académico actualizado correctamente.');

    $this->assertDatabaseHas('school_terms', [
        'id' => $schoolTerm->id,
        'end_date' => '2024-12-15',
    ]);
});

test('admin can delete school term', function () {
    $schoolTerm = SchoolTerm::create([
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType->id,
        'term_type_name' => $this->termType->name,
        'start_date' => '2024-09-01',
        'end_date' => '2024-11-30',
    ]);

    $this->actingAs($this->admin);

    $response = $this->delete("/admin/school-terms/{$schoolTerm->id}");

    $response->assertStatus(302);
    $response->assertRedirect('/admin/school-terms?academic_year_id='.$this->academicYear->id);
    $response->assertSessionHas('success', 'Lapso académico eliminado correctamente.');

    $this->assertSoftDeleted('school_terms', [
        'id' => $schoolTerm->id,
    ]);
});

// ============================================================================
// TESTS: Profesor NO puede gestionar school terms
// ============================================================================

test('profesor cannot view school terms index', function () {
    $this->actingAs($this->profesor);

    $response = $this->get('/admin/school-terms');

    $response->assertForbidden();
});

test('profesor cannot access create school term page', function () {
    $this->actingAs($this->profesor);

    $response = $this->get('/admin/school-terms/create');

    $response->assertForbidden();
});

test('profesor cannot create school term', function () {
    $this->actingAs($this->profesor);

    $response = $this->post('/admin/school-terms', [
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType->id,
        'term_type_name' => $this->termType->name,
        'start_date' => '2024-09-01',
        'end_date' => '2024-11-30',
    ]);

    $response->assertForbidden();

    $this->assertDatabaseMissing('school_terms', [
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType->id,
    ]);
});

test('profesor cannot access edit school term page', function () {
    $schoolTerm = SchoolTerm::create([
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType->id,
        'term_type_name' => $this->termType->name,
        'start_date' => '2024-09-01',
        'end_date' => '2024-11-30',
    ]);

    $this->actingAs($this->profesor);

    $response = $this->get("/admin/school-terms/{$schoolTerm->id}/edit");

    $response->assertForbidden();
});

test('profesor cannot update school term', function () {
    $schoolTerm = SchoolTerm::create([
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType->id,
        'term_type_name' => $this->termType->name,
        'start_date' => '2024-09-01',
        'end_date' => '2024-11-30',
    ]);

    $this->actingAs($this->profesor);

    $response = $this->put("/admin/school-terms/{$schoolTerm->id}", [
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType->id,
        'term_type_name' => $this->termType->name,
        'start_date' => '2024-09-01',
        'end_date' => '2024-12-15',
    ]);

    $response->assertForbidden();

    $this->assertDatabaseHas('school_terms', [
        'id' => $schoolTerm->id,
        'end_date' => '2024-11-30', // No cambió
    ]);
});

test('profesor cannot delete school term', function () {
    $schoolTerm = SchoolTerm::create([
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType->id,
        'term_type_name' => $this->termType->name,
        'start_date' => '2024-09-01',
        'end_date' => '2024-11-30',
    ]);

    $this->actingAs($this->profesor);

    $response = $this->delete("/admin/school-terms/{$schoolTerm->id}");

    $response->assertForbidden();

    $this->assertDatabaseHas('school_terms', [
        'id' => $schoolTerm->id,
        'deleted_at' => null,
    ]);
});

// ============================================================================
// TESTS: Alumno NO puede acceder a school terms
// ============================================================================

test('alumno cannot view school terms index', function () {
    $this->actingAs($this->alumno);

    $response = $this->get('/admin/school-terms');

    $response->assertForbidden();
});

test('alumno cannot access create school term page', function () {
    $this->actingAs($this->alumno);

    $response = $this->get('/admin/school-terms/create');

    $response->assertForbidden();
});

test('alumno cannot create school term', function () {
    $this->actingAs($this->alumno);

    $response = $this->post('/admin/school-terms', [
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType->id,
        'term_type_name' => $this->termType->name,
        'start_date' => '2024-09-01',
        'end_date' => '2024-11-30',
    ]);

    $response->assertForbidden();
});

test('alumno cannot access edit school term page', function () {
    $schoolTerm = SchoolTerm::create([
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType->id,
        'term_type_name' => $this->termType->name,
        'start_date' => '2024-09-01',
        'end_date' => '2024-11-30',
    ]);

    $this->actingAs($this->alumno);

    $response = $this->get("/admin/school-terms/{$schoolTerm->id}/edit");

    $response->assertForbidden();
});

test('alumno cannot update school term', function () {
    $schoolTerm = SchoolTerm::create([
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType->id,
        'term_type_name' => $this->termType->name,
        'start_date' => '2024-09-01',
        'end_date' => '2024-11-30',
    ]);

    $this->actingAs($this->alumno);

    $response = $this->put("/admin/school-terms/{$schoolTerm->id}", [
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType->id,
        'term_type_name' => $this->termType->name,
        'start_date' => '2024-09-01',
        'end_date' => '2024-12-15',
    ]);

    $response->assertForbidden();
});

test('alumno cannot delete school term', function () {
    $schoolTerm = SchoolTerm::create([
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType->id,
        'term_type_name' => $this->termType->name,
        'start_date' => '2024-09-01',
        'end_date' => '2024-11-30',
    ]);

    $this->actingAs($this->alumno);

    $response = $this->delete("/admin/school-terms/{$schoolTerm->id}");

    $response->assertForbidden();
});

// ============================================================================
// TESTS: Representante NO puede acceder a school terms
// ============================================================================

test('representante cannot view school terms index', function () {
    $this->actingAs($this->representante);

    $response = $this->get('/admin/school-terms');

    $response->assertForbidden();
});

test('representante cannot access create school term page', function () {
    $this->actingAs($this->representante);

    $response = $this->get('/admin/school-terms/create');

    $response->assertForbidden();
});

test('representante cannot create school term', function () {
    $this->actingAs($this->representante);

    $response = $this->post('/admin/school-terms', [
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType->id,
        'term_type_name' => $this->termType->name,
        'start_date' => '2024-09-01',
        'end_date' => '2024-11-30',
    ]);

    $response->assertForbidden();
});

test('representante cannot access edit school term page', function () {
    $schoolTerm = SchoolTerm::create([
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType->id,
        'term_type_name' => $this->termType->name,
        'start_date' => '2024-09-01',
        'end_date' => '2024-11-30',
    ]);

    $this->actingAs($this->representante);

    $response = $this->get("/admin/school-terms/{$schoolTerm->id}/edit");

    $response->assertForbidden();
});

test('representante cannot update school term', function () {
    $schoolTerm = SchoolTerm::create([
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType->id,
        'term_type_name' => $this->termType->name,
        'start_date' => '2024-09-01',
        'end_date' => '2024-11-30',
    ]);

    $this->actingAs($this->representante);

    $response = $this->put("/admin/school-terms/{$schoolTerm->id}", [
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType->id,
        'term_type_name' => $this->termType->name,
        'start_date' => '2024-09-01',
        'end_date' => '2024-12-15',
    ]);

    $response->assertForbidden();
});

test('representante cannot delete school term', function () {
    $schoolTerm = SchoolTerm::create([
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType->id,
        'term_type_name' => $this->termType->name,
        'start_date' => '2024-09-01',
        'end_date' => '2024-11-30',
    ]);

    $this->actingAs($this->representante);

    $response = $this->delete("/admin/school-terms/{$schoolTerm->id}");

    $response->assertForbidden();
});

// ============================================================================
// TESTS: Validaciones de negocio para School Terms
// ============================================================================

test('admin cannot create school term without required fields', function () {
    $this->actingAs($this->admin);

    $response = $this->post('/admin/school-terms', [
        // Faltan campos requeridos
    ]);

    $response->assertSessionHasErrors(['academic_year_id', 'term_type_id', 'start_date', 'end_date']);
});

test('admin cannot create school term with end date before start date', function () {
    $this->actingAs($this->admin);

    $response = $this->post('/admin/school-terms', [
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType->id,
        'term_type_name' => $this->termType->name,
        'start_date' => '2024-11-30',
        'end_date' => '2024-09-01', // Antes de start_date
    ]);

    $response->assertSessionHasErrors('end_date');
});

test('admin cannot create duplicate term type for same academic year', function () {
    // Crear primer lapso
    SchoolTerm::create([
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType->id,
        'term_type_name' => $this->termType->name,
        'start_date' => '2024-09-01',
        'end_date' => '2024-11-30',
    ]);

    $this->actingAs($this->admin);

    // Intentar crear otro lapso con el mismo term_type_id
    $response = $this->post('/admin/school-terms', [
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType->id, // Duplicado
        'start_date' => '2024-12-01',
        'end_date' => '2024-12-31',
    ]);

    $response->assertSessionHasErrors('term_type_id');
});


test('admin cannot create overlapping school terms', function () {
    // Crear Lapso 1: 01/09/2024 - 30/11/2024
    SchoolTerm::create([
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType->id,
        'term_type_name' => $this->termType->name,
        'start_date' => '2024-09-01',
        'end_date' => '2024-11-30',
    ]);

    $this->actingAs($this->admin);

    // Intentar crear Lapso 2 que empieza ANTES de que termine el Lapso 1
    $response = $this->post('/admin/school-terms', [
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType2->id,
        'start_date' => '2024-11-15', // Empieza antes de que termine el Lapso 1
        'end_date' => '2024-12-31',
    ]);

    $response->assertSessionHasErrors('start_date');
    
    $this->assertDatabaseMissing('school_terms', [
        'term_type_id' => $this->termType2->id,
    ]);
});

test('admin can create sequential non-overlapping school terms', function () {
    // Crear Lapso 1: 01/09/2024 - 30/11/2024
    SchoolTerm::create([
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType->id,
        'term_type_name' => $this->termType->name,
        'start_date' => '2024-09-01',
        'end_date' => '2024-11-30',
    ]);

    $this->actingAs($this->admin);

    // Crear Lapso 2 que empieza DESPUÉS de que termine el Lapso 1
    $response = $this->post('/admin/school-terms', [
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType2->id,
        'start_date' => '2024-12-01', // Empieza el día siguiente al fin del Lapso 1
        'end_date' => '2025-02-28',
    ]);

    $response->assertStatus(302);
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('school_terms', [
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType2->id,
        'start_date' => '2024-12-01',
        'end_date' => '2025-02-28',
    ]);
});

test('admin cannot create school term that starts inside another term', function () {
    // Crear Lapso 1: 01/09/2024 - 30/11/2024
    SchoolTerm::create([
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType->id,
        'term_type_name' => $this->termType->name,
        'start_date' => '2024-09-01',
        'end_date' => '2024-11-30',
    ]);

    $this->actingAs($this->admin);

    // Intentar crear Lapso 2 que empieza DENTRO del Lapso 1
    $response = $this->post('/admin/school-terms', [
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType2->id,
        'start_date' => '2024-10-01', // Dentro del Lapso 1
        'end_date' => '2024-12-31',
    ]);

    $response->assertSessionHasErrors('start_date');
});

test('admin cannot create school term that ends inside another term', function () {
    // Crear Lapso 1: 01/09/2024 - 30/11/2024
    SchoolTerm::create([
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType->id,
        'term_type_name' => $this->termType->name,
        'start_date' => '2024-09-01',
        'end_date' => '2024-11-30',
    ]);

    $this->actingAs($this->admin);

    // Intentar crear Lapso 2 que termina DENTRO del Lapso 1
    $response = $this->post('/admin/school-terms', [
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType2->id,
        'start_date' => '2024-08-01',
        'end_date' => '2024-10-15', // Termina dentro del Lapso 1
    ]);

    $response->assertSessionHasErrors('start_date');
});

test('admin cannot create school term that completely contains another term', function () {
    // Crear Lapso 1: 01/10/2024 - 31/10/2024
    SchoolTerm::create([
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType->id,
        'term_type_name' => $this->termType->name,
        'start_date' => '2024-10-01',
        'end_date' => '2024-10-31',
    ]);

    $this->actingAs($this->admin);

    // Intentar crear Lapso 2 que CONTIENE completamente al Lapso 1
    $response = $this->post('/admin/school-terms', [
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $this->termType2->id,
        'start_date' => '2024-09-01', // Antes del Lapso 1
        'end_date' => '2024-11-30',   // Después del Lapso 1
    ]);

    $response->assertSessionHasErrors('start_date');
});


