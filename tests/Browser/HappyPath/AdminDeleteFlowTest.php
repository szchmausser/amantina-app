<?php

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\SchoolTerm;
use App\Models\Section;
use App\Models\TermType;
use App\Models\User;
use Database\Seeders\FieldSessionStatusSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\TermTypeSeeder;

/**
 * Happy Path: Admin Delete Flow
 *
 * Este test verifica que los administradores pueden eliminar entidades (soft delete).
 * Cubre la Fase 3 del plan de cobertura de tests browser.
 *
 * Tests incluidos:
 * 1. Eliminar Año Escolar
 * 2. Eliminar Lapso Académico
 * 3. Eliminar Grado
 * 4. Eliminar Sección
 * 5. Eliminar Usuario
 */
beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->seed(TermTypeSeeder::class);
    $this->seed(FieldSessionStatusSeeder::class);

    $this->admin = User::factory()->create([
        'email' => 'admin@deletetest.com',
        'password' => bcrypt('password'),
    ]);
    $this->admin->assignRole('admin');

    $this->actingAs($this->admin);
});

// ============================================================================
// FASE 3.1: Eliminar Año Escolar
// ============================================================================

test('admin puede eliminar un año escolar', function () {
    // Crear año escolar con factory
    $academicYear = AcademicYear::factory()->create([
        'name' => '2024-2025',
        'start_date' => '2024-09-01',
        'end_date' => '2025-07-31',
        'required_hours' => 500,
        'is_active' => false,
    ]);

    // Navegar a la página de listado
    $page = visit('/admin/academic-years');
    $page->wait(2);
    $page->assertSee('2024-2025');

    // Click en botón eliminar (abre el AlertDialog)
    // Usar selector CSS: botón con clase text-red-500 (el botón de eliminar)
    $page->click('button.text-red-500');
    $page->wait(1);

    // Verificar que el AlertDialog aparece
    $page->assertSee('¿Eliminar año escolar?');

    // Confirmar eliminación
    $page->click('[data-test="confirm-delete-button"]');
    $page->wait(3);

    // Verificar que redirigió al listado
    expect($page->url())->toContain('/admin/academic-years');

    // Verificar soft delete en base de datos
    $this->assertSoftDeleted('academic_years', [
        'id' => $academicYear->id,
    ]);

    // Verificar que no aparece en el listado
    $page->assertDontSee('2024-2025');
});

// ============================================================================
// FASE 3.2: Eliminar Lapso Académico
// ============================================================================

test('admin puede eliminar un lapso académico', function () {
    // Crear año escolar y lapso con factory
    $academicYear = AcademicYear::factory()->create([
        'name' => '2024-2025',
        'start_date' => '2024-09-01',
        'end_date' => '2025-07-31',
        'is_active' => true,
    ]);

    $termType = TermType::where('name', 'Lapso 1')->first();

    $schoolTerm = SchoolTerm::factory()->create([
        'academic_year_id' => $academicYear->id,
        'term_type_id' => $termType->id,
        'start_date' => '2024-09-01',
        'end_date' => '2024-12-15',
    ]);

    // Navegar a la página de listado
    $page = visit('/admin/school-terms');
    $page->wait(2);

    // Verificar que el lapso aparece (por el nombre del tipo de lapso)
    $page->assertSee('Lapso 1');

    // Click en botón eliminar (abre el AlertDialog)
    $page->click('button.text-red-500');
    $page->wait(1);

    // Verificar que el AlertDialog aparece
    $page->assertSee('¿Eliminar lapso académico?');

    // Confirmar eliminación
    $page->click('[data-test="confirm-delete-button"]');
    $page->wait(3);

    // Verificar que redirigió al listado
    expect($page->url())->toContain('/admin/school-terms');

    // Verificar soft delete en base de datos
    $this->assertSoftDeleted('school_terms', [
        'id' => $schoolTerm->id,
    ]);
});

// ============================================================================
// FASE 3.3: Eliminar Grado
// ============================================================================

test('admin puede eliminar un grado', function () {
    // Crear año escolar y grado con factory
    $academicYear = AcademicYear::factory()->create([
        'name' => '2024-2025',
        'start_date' => '2024-09-01',
        'end_date' => '2025-07-31',
        'is_active' => true,
    ]);

    $grade = Grade::factory()->create([
        'academic_year_id' => $academicYear->id,
        'name' => '1er Año',
        'order' => 1,
    ]);

    // Navegar a la página de listado
    $page = visit('/admin/grades');
    $page->wait(2);
    $page->assertSee('1er Año');

    // Click en botón eliminar (abre el AlertDialog)
    $page->click('button.text-red-500');
    $page->wait(1);

    // Verificar que el AlertDialog aparece
    $page->assertSee('¿Eliminar grado?');

    // Confirmar eliminación
    $page->click('[data-test="confirm-delete-button"]');
    $page->wait(3);

    // Verificar que redirigió al listado
    expect($page->url())->toContain('/admin/grades');

    // Verificar soft delete en base de datos
    $this->assertSoftDeleted('grades', [
        'id' => $grade->id,
    ]);

    // Verificar que no aparece en el listado
    $page->assertDontSee('1er Año');
});

// ============================================================================
// FASE 3.4: Eliminar Sección
// ============================================================================

test('admin puede eliminar una sección', function () {
    // Crear estructura completa con factory
    $academicYear = AcademicYear::factory()->create([
        'name' => '2024-2025',
        'start_date' => '2024-09-01',
        'end_date' => '2025-07-31',
        'is_active' => true,
    ]);

    $grade = Grade::factory()->create([
        'academic_year_id' => $academicYear->id,
        'name' => '1er Año',
        'order' => 1,
    ]);

    $section = Section::factory()->create([
        'grade_id' => $grade->id,
        'name' => 'Sección A',
    ]);

    // Navegar a la página de listado
    $page = visit('/admin/sections');
    $page->wait(2);
    // Verificar que la sección aparece
    $page->assertSee('Sección A');

    // Click en botón eliminar (abre el AlertDialog)
    $page->click('button.text-red-500');
    $page->wait(1);

    // Verificar que el AlertDialog aparece
    $page->assertSee('Confirmar Eliminación');

    // Confirmar eliminación
    $page->click('[data-test="confirm-delete-button"]');
    $page->wait(3);

    // Verificar que redirigió al listado
    expect($page->url())->toContain('/admin/sections');

    // Verificar soft delete en base de datos
    $this->assertSoftDeleted('sections', [
        'id' => $section->id,
    ]);

    // Verificar que no aparece en el listado
    $page->assertDontSee('Sección A');
});

// ============================================================================
// FASE 3.5: Eliminar Usuario
// ============================================================================

test('admin puede eliminar un usuario', function () {
    // Crear usuario con factory
    $user = User::factory()->create([
        'cedula' => 'V-12345678',
        'name' => 'Juan Pérez',
        'email' => 'juan.perez@deletetest.com',
    ]);
    $user->assignRole('alumno');

    // Navegar a la página de listado
    $page = visit('/admin/users');
    $page->wait(2);
    $page->assertSee('Juan Pérez');

    // Click en botón eliminar del usuario Juan Pérez (no del admin)
    // Usar un selector más específico: buscar la fila que contiene "Juan Pérez" y hacer click en su botón eliminar
    $page->click('tr:has-text("Juan Pérez") button.text-red-500');
    $page->wait(1);

    // Verificar que el AlertDialog aparece
    $page->assertSee('Confirmar Eliminación');

    // Confirmar eliminación
    $page->click('[data-test="confirm-delete-button"]');
    $page->wait(3);

    // Verificar que redirigió al listado
    expect($page->url())->toContain('/admin/users');

    // Verificar soft delete en base de datos
    $this->assertSoftDeleted('users', [
        'id' => $user->id,
    ]);

    // Verificar que no aparece en el listado
    $page->assertDontSee('Juan Pérez');
});

// ============================================================================
// FASE 3.6: Desinscribir Alumno
// ============================================================================

test('admin puede desinscribir un alumno de una sección', function () {
    // Crear estructura completa con factory
    $academicYear = AcademicYear::factory()->create([
        'name' => '2025-2026',
        'start_date' => '2025-09-01',
        'end_date' => '2026-07-31',
        'is_active' => true,
    ]);

    $grade = Grade::factory()->create([
        'academic_year_id' => $academicYear->id,
        'name' => '1er Año',
        'order' => 1,
    ]);

    $section = Section::factory()->create([
        'grade_id' => $grade->id,
        'name' => 'Sección A',
    ]);

    $student = User::factory()->create([
        'cedula' => 'V-99999999',
        'name' => 'Pedro Martínez',
        'email' => 'pedro.martinez@test.com',
    ]);
    $student->assignRole('alumno');

    // Crear inscripción
    $enrollment = \App\Models\Enrollment::factory()->create([
        'academic_year_id' => $academicYear->id,
        'grade_id' => $grade->id,
        'section_id' => $section->id,
        'user_id' => $student->id,
    ]);

    // Navegar a la página de inscripciones
    $page = visit('/admin/enrollments');
    $page->wait(2);

    // Verificar que el alumno aparece en el listado
    $page->assertSee('Pedro Martínez');
    $page->assertSee('V-99999999');

    // Click en botón eliminar (papelera) del alumno
    // Usar selector específico para evitar conflictos
    $page->click('tr:has-text("Pedro Martínez") button.text-red-500');
    $page->wait(1);

    // Verificar que el AlertDialog aparece
    $page->assertSee('Confirmar Eliminación');

    // Confirmar eliminación
    $page->click('[data-test="confirm-delete-button"]');
    $page->wait(3);

    // Verificar que redirigió al listado
    expect($page->url())->toContain('/admin/enrollments');

    // Verificar soft delete en base de datos
    $this->assertSoftDeleted('enrollments', [
        'id' => $enrollment->id,
    ]);

    // Verificar que no aparece en el listado
    $page->assertDontSee('Pedro Martínez');
});

// ============================================================================
// FASE 3.7: Desasignar Profesor
// ============================================================================

test('admin puede desasignar un profesor de una sección', function () {
    // Crear estructura completa con factory
    $academicYear = AcademicYear::factory()->create([
        'name' => '2025-2026',
        'start_date' => '2025-09-01',
        'end_date' => '2026-07-31',
        'is_active' => true,
    ]);

    $grade = Grade::factory()->create([
        'academic_year_id' => $academicYear->id,
        'name' => '1er Año',
        'order' => 1,
    ]);

    $section = Section::factory()->create([
        'grade_id' => $grade->id,
        'name' => 'Sección A',
    ]);

    $teacher = User::factory()->create([
        'cedula' => 'V-88888888',
        'name' => 'Prof. Laura Gómez',
        'email' => 'laura.gomez@test.com',
    ]);
    $teacher->assignRole('profesor');

    // Crear asignación de profesor a sección
    $assignment = \App\Models\TeacherAssignment::factory()->create([
        'academic_year_id' => $academicYear->id,
        'user_id' => $teacher->id,
        'section_id' => $section->id,
    ]);

    // Navegar a la página de asignaciones
    $page = visit('/admin/teacher-assignments/create');
    $page->wait(2);

    // Seleccionar el profesor (click en card)
    $page->click('[data-test="teacher-item-'.$teacher->id.'"]');
    $page->wait(2);

    // Verificar que la sección aparece (el profesor ya está asignado)
    $page->assertSee('Sección A');
    $page->assertSee('Ya asignado'); // Badge que indica que ya estaba asignado

    // Desmarcar la sección (click en card para toggle)
    // Esto debe abrir un AlertDialog de confirmación
    $page->click('[data-test="section-checkbox-'.$section->id.'"]');
    $page->wait(1);

    // Verificar que aparece el AlertDialog de confirmación de desasignación
    $page->assertSee('Desasignar Sección');
    $page->assertSee('El cambio se aplicará cuando hagas click en "Guardar Cambios"');

    // Confirmar la desasignación (esto solo desmarca, no guarda todavía)
    $page->click('[data-test="confirm-unassign-button"]');
    $page->wait(1);

    // Verificar que el badge "Ya asignado" desapareció
    $page->assertDontSee('Ya asignado');

    // Click en botón "Guardar Cambios" (debe estar habilitado porque hay cambios)
    $page->click('[data-test="save-assignments-button"]');
    $page->wait(1);

    // Verificar que el AlertDialog de guardar aparece
    $page->assertSee('Confirmar Asignación');
    $page->assertSee('0 sección(es)'); // Ahora no hay secciones asignadas

    // Confirmar guardado
    $page->click('[data-test="confirm-save-button"]');
    $page->wait(3);

    // Verificar que redirigió correctamente
    expect($page->url())->toContain('/admin/teacher-assignments/create');

    // Verificar soft delete en base de datos
    $this->assertSoftDeleted('teacher_assignments', [
        'id' => $assignment->id,
    ]);
});
