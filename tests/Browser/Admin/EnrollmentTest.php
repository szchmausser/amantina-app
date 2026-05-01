<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Section;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->admin = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
    ]);
    $this->admin->assignRole('admin');

    $this->actingAs($this->admin);

    $this->academicYear = AcademicYear::factory()->create([
        'name' => '2024-2025',
        'is_active' => true,
    ]);

    $this->grade = Grade::factory()->for($this->academicYear)->create(['name' => '1er Año']);
    $this->section = Section::factory()->for($this->academicYear)->for($this->grade)->create(['name' => 'A']);
});

test('admin puede ver el listado de inscripciones', function () {
    $page = visit('/admin/enrollments');

    $page->assertPathIs('/admin/enrollments')
        ->assertSee('Inscripciones')
        ->assertNoJavaScriptErrors();
});

test('admin puede ver inscripciones existentes', function () {
    $student = User::factory()->create(['name' => 'Pedro Inscrito']);
    $student->assignRole('alumno');

    Enrollment::factory()
        ->for($this->academicYear)
        ->for($this->grade)
        ->for($this->section)
        ->for($student, 'student')
        ->create();

    $page = visit('/admin/enrollments');

    $page->assertSee('Pedro Inscrito')
        ->assertNoJavaScriptErrors();
});

test('admin puede acceder al formulario de inscripción', function () {
    $page = visit('/admin/enrollments/create');

    $page->assertPathIs('/admin/enrollments/create')
        ->assertNoJavaScriptErrors();
});

test('admin puede ver el panel de promoción de estudiantes', function () {
    $page = visit('/admin/enrollments/promote');

    $page->assertPathIs('/admin/enrollments/promote')
        ->assertNoJavaScriptErrors();
});

test('alumno inscrito aparece en la sección correcta', function () {
    $student = User::factory()->create(['name' => 'Ana Sección A']);
    $student->assignRole('alumno');

    Enrollment::factory()
        ->for($this->academicYear)
        ->for($this->grade)
        ->for($this->section)
        ->for($student, 'student')
        ->create();

    $page = visit('/admin/sections/'.$this->section->id);

    $page->assertSee('Ana Sección A')
        ->assertNoJavaScriptErrors();
});

// ============================================================================
// TESTS DE SEGURIDAD BASADOS EN PERMISOS
// ============================================================================

test('usuario con permiso enrollments.view SÍ puede ver listado', function () {
    $profesor = User::factory()->create();
    $profesor->assignRole('profesor'); // profesor SÍ tiene enrollments.view

    $this->actingAs($profesor);

    $page = visit('/admin/enrollments');

    $page->assertPathIs('/admin/enrollments')
        ->assertSee('Inscripciones')
        ->assertNoJavaScriptErrors();
});

test('usuario sin permiso enrollments.view NO puede ver listado', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno'); // alumno NO tiene enrollments.view

    $this->actingAs($alumno);

    $page = visit('/admin/enrollments');

    $page->assertSee('403');
});

test('usuario sin permiso enrollments.create NO puede acceder a formulario de creación', function () {
    $profesor = User::factory()->create();
    $profesor->assignRole('profesor'); // profesor NO tiene enrollments.create

    $this->actingAs($profesor);

    $page = visit('/admin/enrollments/create');

    $page->assertSee('403');
});

test('usuario sin permiso enrollments.create NO puede acceder a formulario de creación (alumno)', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno'); // alumno NO tiene enrollments.create

    $this->actingAs($alumno);

    $page = visit('/admin/enrollments/create');

    $page->assertSee('403');
});

test('usuario sin permiso enrollments.create NO puede crear inscripción mediante POST (alumno)', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno'); // alumno NO tiene enrollments.create

    $this->actingAs($alumno);

    $response = $this->post('/admin/enrollments', [
        'user_id' => $alumno->id,
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'section_id' => $this->section->id,
    ]);

    $response->assertStatus(403);

    // Verificar que NO se creó la inscripción
    $this->assertDatabaseMissing('enrollments', [
        'user_id' => $alumno->id,
        'academic_year_id' => $this->academicYear->id,
    ]);
});

test('usuario sin permiso enrollments.create NO puede crear inscripción mediante POST (profesor)', function () {
    $profesor = User::factory()->create();
    $profesor->assignRole('profesor'); // profesor NO tiene enrollments.create

    $student = User::factory()->create();
    $student->assignRole('alumno');

    $this->actingAs($profesor);

    $response = $this->post('/admin/enrollments', [
        'user_id' => $student->id,
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'section_id' => $this->section->id,
    ]);

    $response->assertStatus(403);

    // Verificar que NO se creó la inscripción
    $this->assertDatabaseMissing('enrollments', [
        'user_id' => $student->id,
        'academic_year_id' => $this->academicYear->id,
    ]);
});

test('alumno NO puede inscribirse a sí mismo mediante POST directo', function () {
    $alumno = User::factory()->create(['name' => 'Alumno Malicioso']);
    $alumno->assignRole('alumno'); // alumno NO tiene enrollments.create

    $this->actingAs($alumno);

    // Intento de auto-inscripción
    $response = $this->post('/admin/enrollments', [
        'user_id' => $alumno->id,
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'section_id' => $this->section->id,
    ]);

    $response->assertStatus(403);

    // Verificar que NO se creó la inscripción
    $this->assertDatabaseMissing('enrollments', [
        'user_id' => $alumno->id,
        'academic_year_id' => $this->academicYear->id,
    ]);
});

test('usuario sin permiso enrollments.delete NO puede eliminar inscripción mediante DELETE (alumno)', function () {
    $student = User::factory()->create();
    $student->assignRole('alumno');

    $enrollment = Enrollment::factory()
        ->for($this->academicYear)
        ->for($this->grade)
        ->for($this->section)
        ->for($student, 'student')
        ->create();

    $alumno = User::factory()->create();
    $alumno->assignRole('alumno'); // alumno NO tiene enrollments.delete

    $this->actingAs($alumno);

    $response = $this->delete("/admin/enrollments/{$enrollment->id}");

    $response->assertStatus(403);

    // Verificar que NO se eliminó
    $this->assertDatabaseHas('enrollments', [
        'id' => $enrollment->id,
    ]);
});

test('usuario sin permiso enrollments.delete NO puede eliminar inscripción mediante DELETE (profesor)', function () {
    $student = User::factory()->create();
    $student->assignRole('alumno');

    $enrollment = Enrollment::factory()
        ->for($this->academicYear)
        ->for($this->grade)
        ->for($this->section)
        ->for($student, 'student')
        ->create();

    $profesor = User::factory()->create();
    $profesor->assignRole('profesor'); // profesor NO tiene enrollments.delete

    $this->actingAs($profesor);

    $response = $this->delete("/admin/enrollments/{$enrollment->id}");

    $response->assertStatus(403);

    // Verificar que NO se eliminó
    $this->assertDatabaseHas('enrollments', [
        'id' => $enrollment->id,
    ]);
});

test('usuario sin permiso enrollments.view NO puede acceder a ninguna ruta (representante)', function () {
    $representante = User::factory()->create();
    $representante->assignRole('representante'); // representante NO tiene enrollments.view

    $this->actingAs($representante);

    $page = visit('/admin/enrollments');

    $page->assertSee('403');
});

// ============================================================================
// TESTS POSITIVOS DE ADMIN (COMPLETAR COBERTURA 100%)
// ============================================================================

test('usuario CON permiso enrollments.create SÍ puede crear inscripción mediante POST (admin)', function () {
    $student = User::factory()->create();
    $student->assignRole('alumno');

    $this->actingAs($this->admin); // admin SÍ tiene enrollments.create

    $response = $this->post('/admin/enrollments', [
        'user_ids' => [$student->id], // Debe ser array
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'section_id' => $this->section->id,
    ]);

    $response->assertStatus(302); // Redirección exitosa

    // Verificar que SÍ se creó la inscripción
    $this->assertDatabaseHas('enrollments', [
        'user_id' => $student->id,
        'academic_year_id' => $this->academicYear->id,
        'grade_id' => $this->grade->id,
        'section_id' => $this->section->id,
    ]);
});

test('usuario CON permiso enrollments.delete SÍ puede eliminar inscripción mediante DELETE (admin)', function () {
    $student = User::factory()->create();
    $student->assignRole('alumno');

    $enrollment = Enrollment::factory()
        ->for($this->academicYear)
        ->for($this->grade)
        ->for($this->section)
        ->for($student)
        ->create();

    $this->actingAs($this->admin); // admin SÍ tiene enrollments.delete

    $response = $this->delete("/admin/enrollments/{$enrollment->id}");

    $response->assertStatus(302); // Redirección exitosa

    // Verificar que SÍ se eliminó (soft delete)
    $this->assertSoftDeleted('enrollments', [
        'id' => $enrollment->id,
    ]);
});
