<?php

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\Section;
use App\Models\TeacherAssignment;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    // Crear usuarios con roles
    $this->admin = User::factory()->create(['name' => 'Admin User']);
    $this->admin->assignRole('admin');

    $this->teacher = User::factory()->create(['name' => 'Teacher User']);
    $this->teacher->assignRole('profesor');

    $this->student = User::factory()->create(['name' => 'Student User']);
    $this->student->assignRole('alumno');

    $this->representative = User::factory()->create(['name' => 'Representative User']);
    $this->representative->assignRole('representante');

    // Crear estructura académica
    $this->activeYear = AcademicYear::factory()->create([
        'name' => '2025-2026',
        'is_active' => true,
    ]);
    $this->grade = Grade::factory()->create([
        'academic_year_id' => $this->activeYear->id,
        'name' => '1er Año',
    ]);
    $this->section = Section::factory()->create([
        'grade_id' => $this->grade->id,
        'name' => 'Sección A',
    ]);

    // Crear otro profesor para asignaciones
    $this->anotherTeacher = User::factory()->create(['name' => 'Another Teacher']);
    $this->anotherTeacher->assignRole('profesor');
});

// ============================================================================
// TESTS: Admin puede gestionar asignaciones (CRUD completo)
// ============================================================================

test('admin can view teacher assignments page', function () {
    $this->actingAs($this->admin);

    $response = $this->get('/admin/teacher-assignments');

    $response->assertStatus(302); // Redirige a create
    $response->assertRedirect('/admin/teacher-assignments/create');
});

test('admin can access create teacher assignments page', function () {
    $this->actingAs($this->admin);

    $response = $this->get('/admin/teacher-assignments/create');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('admin/assignments/create'));
});

test('admin can create teacher assignment', function () {
    $this->actingAs($this->admin);

    $response = $this->post('/admin/teacher-assignments', [
        'user_id' => $this->teacher->id,
        'academic_year_id' => $this->activeYear->id,
        'section_ids' => [$this->section->id],
    ]);

    $response->assertStatus(302);
    $response->assertRedirect('/admin/teacher-assignments/create');
    $response->assertSessionHas('success', 'Asignaciones del profesor actualizadas correctamente.');

    $this->assertDatabaseHas('teacher_assignments', [
        'user_id' => $this->teacher->id,
        'section_id' => $this->section->id,
        'academic_year_id' => $this->activeYear->id,
    ]);
});

test('admin can update teacher assignments by adding sections', function () {
    // Crear asignación inicial
    TeacherAssignment::factory()->create([
        'user_id' => $this->teacher->id,
        'academic_year_id' => $this->activeYear->id,
        'grade_id' => $this->grade->id,
        'section_id' => $this->section->id,
    ]);

    // Crear nueva sección
    $newSection = Section::factory()->create([
        'grade_id' => $this->grade->id,
        'name' => 'Sección B',
    ]);

    $this->actingAs($this->admin);

    $response = $this->post('/admin/teacher-assignments', [
        'user_id' => $this->teacher->id,
        'academic_year_id' => $this->activeYear->id,
        'section_ids' => [$this->section->id, $newSection->id],
    ]);

    $response->assertStatus(302);
    $response->assertSessionHas('success');

    // Verificar que ambas secciones están asignadas
    $this->assertDatabaseHas('teacher_assignments', [
        'user_id' => $this->teacher->id,
        'section_id' => $this->section->id,
    ]);
    $this->assertDatabaseHas('teacher_assignments', [
        'user_id' => $this->teacher->id,
        'section_id' => $newSection->id,
    ]);
});

test('admin can update teacher assignments by removing sections', function () {
    // Crear dos asignaciones
    $section2 = Section::factory()->create([
        'grade_id' => $this->grade->id,
        'name' => 'Sección B',
    ]);

    TeacherAssignment::factory()->create([
        'user_id' => $this->teacher->id,
        'academic_year_id' => $this->activeYear->id,
        'grade_id' => $this->grade->id,
        'section_id' => $this->section->id,
    ]);

    TeacherAssignment::factory()->create([
        'user_id' => $this->teacher->id,
        'academic_year_id' => $this->activeYear->id,
        'grade_id' => $this->grade->id,
        'section_id' => $section2->id,
    ]);

    $this->actingAs($this->admin);

    $response = $this->post('/admin/teacher-assignments', [
        'user_id' => $this->teacher->id,
        'academic_year_id' => $this->activeYear->id,
        'section_ids' => [$this->section->id], // Solo una sección
    ]);

    $response->assertStatus(302);
    $response->assertSessionHas('success');

    // Verificar que solo queda una asignación
    $this->assertDatabaseHas('teacher_assignments', [
        'user_id' => $this->teacher->id,
        'section_id' => $this->section->id,
        'deleted_at' => null,
    ]);

    $this->assertDatabaseMissing('teacher_assignments', [
        'user_id' => $this->teacher->id,
        'section_id' => $section2->id,
        'deleted_at' => null,
    ]);
});

test('admin can delete teacher assignment', function () {
    $assignment = TeacherAssignment::factory()->create([
        'user_id' => $this->teacher->id,
        'academic_year_id' => $this->activeYear->id,
        'grade_id' => $this->grade->id,
        'section_id' => $this->section->id,
    ]);

    $this->actingAs($this->admin);

    $response = $this->delete("/admin/teacher-assignments/{$assignment->id}");

    $response->assertStatus(302);
    $response->assertRedirect('/admin/teacher-assignments');
    $response->assertSessionHas('success', 'Asignación eliminada correctamente.');

    $this->assertSoftDeleted('teacher_assignments', [
        'id' => $assignment->id,
    ]);
});

// ============================================================================
// TESTS: Profesor NO puede ver ni gestionar asignaciones
// ============================================================================

test('teacher cannot view teacher assignments page', function () {
    $this->actingAs($this->teacher);

    $response = $this->get('/admin/teacher-assignments');

    $response->assertForbidden();
});

test('teacher cannot access create page', function () {
    $this->actingAs($this->teacher);

    $response = $this->get('/admin/teacher-assignments/create');

    $response->assertForbidden();
});

test('teacher cannot create teacher assignment', function () {
    $this->actingAs($this->teacher);

    $response = $this->post('/admin/teacher-assignments', [
        'user_id' => $this->anotherTeacher->id,
        'academic_year_id' => $this->activeYear->id,
        'section_ids' => [$this->section->id],
    ]);

    $response->assertForbidden();

    $this->assertDatabaseMissing('teacher_assignments', [
        'user_id' => $this->anotherTeacher->id,
        'section_id' => $this->section->id,
    ]);
});

test('teacher cannot delete teacher assignment', function () {
    $assignment = TeacherAssignment::factory()->create([
        'user_id' => $this->anotherTeacher->id,
        'academic_year_id' => $this->activeYear->id,
        'grade_id' => $this->grade->id,
        'section_id' => $this->section->id,
    ]);

    $this->actingAs($this->teacher);

    $response = $this->delete("/admin/teacher-assignments/{$assignment->id}");

    $response->assertForbidden();

    $this->assertDatabaseHas('teacher_assignments', [
        'id' => $assignment->id,
        'deleted_at' => null,
    ]);
});

// ============================================================================
// TESTS: Alumno NO puede acceder a ninguna ruta
// ============================================================================

test('student cannot view teacher assignments page', function () {
    $this->actingAs($this->student);

    $response = $this->get('/admin/teacher-assignments');

    $response->assertForbidden();
});

test('student cannot access create page', function () {
    $this->actingAs($this->student);

    $response = $this->get('/admin/teacher-assignments/create');

    $response->assertForbidden();
});

test('student cannot create teacher assignment', function () {
    $this->actingAs($this->student);

    $response = $this->post('/admin/teacher-assignments', [
        'user_id' => $this->teacher->id,
        'academic_year_id' => $this->activeYear->id,
        'section_ids' => [$this->section->id],
    ]);

    $response->assertForbidden();

    $this->assertDatabaseMissing('teacher_assignments', [
        'user_id' => $this->teacher->id,
        'section_id' => $this->section->id,
    ]);
});

test('student cannot delete teacher assignment', function () {
    $assignment = TeacherAssignment::factory()->create([
        'user_id' => $this->teacher->id,
        'academic_year_id' => $this->activeYear->id,
        'grade_id' => $this->grade->id,
        'section_id' => $this->section->id,
    ]);

    $this->actingAs($this->student);

    $response = $this->delete("/admin/teacher-assignments/{$assignment->id}");

    $response->assertForbidden();

    $this->assertDatabaseHas('teacher_assignments', [
        'id' => $assignment->id,
        'deleted_at' => null,
    ]);
});

// ============================================================================
// TESTS: Representante NO puede acceder a ninguna ruta
// ============================================================================

test('representative cannot view teacher assignments page', function () {
    $this->actingAs($this->representative);

    $response = $this->get('/admin/teacher-assignments');

    $response->assertForbidden();
});

test('representative cannot access create page', function () {
    $this->actingAs($this->representative);

    $response = $this->get('/admin/teacher-assignments/create');

    $response->assertForbidden();
});

test('representative cannot create teacher assignment', function () {
    $this->actingAs($this->representative);

    $response = $this->post('/admin/teacher-assignments', [
        'user_id' => $this->teacher->id,
        'academic_year_id' => $this->activeYear->id,
        'section_ids' => [$this->section->id],
    ]);

    $response->assertForbidden();

    $this->assertDatabaseMissing('teacher_assignments', [
        'user_id' => $this->teacher->id,
        'section_id' => $this->section->id,
    ]);
});

test('representative cannot delete teacher assignment', function () {
    $assignment = TeacherAssignment::factory()->create([
        'user_id' => $this->teacher->id,
        'academic_year_id' => $this->activeYear->id,
        'grade_id' => $this->grade->id,
        'section_id' => $this->section->id,
    ]);

    $this->actingAs($this->representative);

    $response = $this->delete("/admin/teacher-assignments/{$assignment->id}");

    $response->assertForbidden();

    $this->assertDatabaseHas('teacher_assignments', [
        'id' => $assignment->id,
        'deleted_at' => null,
    ]);
});

// ============================================================================
// TESTS: Validaciones de negocio
// ============================================================================

test('admin cannot assign non-teacher user to section', function () {
    $this->actingAs($this->admin);

    $response = $this->post('/admin/teacher-assignments', [
        'user_id' => $this->student->id, // Alumno, no profesor
        'academic_year_id' => $this->activeYear->id,
        'section_ids' => [$this->section->id],
    ]);

    $response->assertSessionHasErrors('user_id');

    $this->assertDatabaseMissing('teacher_assignments', [
        'user_id' => $this->student->id,
        'section_id' => $this->section->id,
    ]);
});

test('admin cannot create assignment without required fields', function () {
    $this->actingAs($this->admin);

    $response = $this->post('/admin/teacher-assignments', [
        // Faltan campos requeridos
    ]);

    $response->assertSessionHasErrors(['user_id', 'academic_year_id']);
});

test('admin cannot assign to inactive academic year', function () {
    $inactiveYear = AcademicYear::factory()->create([
        'name' => '2024-2025',
        'is_active' => false,
    ]);

    $this->actingAs($this->admin);

    $response = $this->post('/admin/teacher-assignments', [
        'user_id' => $this->teacher->id,
        'academic_year_id' => $inactiveYear->id,
        'section_ids' => [$this->section->id],
    ]);

    $response->assertSessionHasErrors('academic_year_id');

    $this->assertDatabaseMissing('teacher_assignments', [
        'user_id' => $this->teacher->id,
        'academic_year_id' => $inactiveYear->id,
    ]);
});
