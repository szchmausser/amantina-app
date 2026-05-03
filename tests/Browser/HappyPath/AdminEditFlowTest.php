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
 * Happy Path: Admin Edit Flow
 *
 * Este test verifica que los administradores pueden editar entidades existentes.
 * Cubre la Fase 2 del plan de cobertura de tests browser.
 *
 * Tests incluidos:
 * 1. Editar Año Escolar
 * 2. Editar Lapso Académico
 * 3. Editar Grado
 * 4. Editar Sección
 * 5. Editar Usuario
 */
beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->seed(TermTypeSeeder::class);
    $this->seed(FieldSessionStatusSeeder::class);

    $this->admin = User::factory()->create([
        'email' => 'admin@edittest.com',
        'password' => bcrypt('password'),
    ]);
    $this->admin->assignRole('admin');

    $this->actingAs($this->admin);
});

// ============================================================================
// FASE 2.1: Editar Año Escolar
// ============================================================================

test('admin puede editar un año escolar existente', function () {
    // Crear año escolar con factory
    $academicYear = AcademicYear::factory()->create([
        'name' => '2024-2025',
        'start_date' => '2024-09-01',
        'end_date' => '2025-07-31',
        'required_hours' => 500,
        'is_active' => false,
    ]);

    // Navegar a la página de edición
    $page = visit("/admin/academic-years/{$academicYear->id}/edit");
    $page->wait(2);
    $page->assertSee('Editar Año Escolar');
    $page->assertSee('2024-2025'); // Verificar que el nombre aparece en la página

    // Modificar solo el nombre (más simple y seguro)
    $page->clear('#name');
    $page->wait(0.2);
    $page->type('#name', '2024-2025 Modificado');
    $page->wait(0.5);

    // Submit y esperar navegación
    $page->click('button[type="submit"]');

    // Esperar que Inertia complete la navegación
    $page->wait(2);

    // Verificar que redirigió al índice
    expect($page->url())->toContain('/admin/academic-years');
    expect($page->url())->not->toContain('/edit');

    // Verificar cambios en base de datos
    $this->assertDatabaseHas('academic_years', [
        'id' => $academicYear->id,
        'name' => '2024-2025 Modificado',
    ]);
});

// ============================================================================
// FASE 2.2: Editar Lapso Académico
// ============================================================================

test('admin puede editar un lapso académico existente', function () {
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

    // Navegar a la página de edición
    $page = visit("/admin/school-terms/{$schoolTerm->id}/edit");
    $page->wait(2);
    $page->assertSee('Editar Lapso Académico');

    // Modificar solo la fecha de fin (más simple y seguro, y no viola validación)
    $page->clear('#end_date');
    $page->wait(0.2);
    $page->type('#end_date', '2024-12-20');
    $page->wait(0.5);

    // Submit
    $page->click('button[type="submit"]');
    $page->wait(5);

    // Verificar redirección (la URL ya no debe contener /edit)
    expect($page->url())->toContain('/admin/school-terms');
    expect($page->url())->not->toContain('/edit');

    // Verificar cambios en base de datos
    $this->assertDatabaseHas('school_terms', [
        'id' => $schoolTerm->id,
        'end_date' => '2024-12-20',
    ]);
});

// ============================================================================
// FASE 2.3: Editar Grado
// ============================================================================

test('admin puede editar un grado existente', function () {
    // Crear año escolar y grado con factory
    $academicYear = AcademicYear::factory()->create([
        'name' => '2024-2025',
        'is_active' => true,
    ]);

    $grade = Grade::factory()->create([
        'academic_year_id' => $academicYear->id,
        'name' => '1er Año',
        'order' => 1,
    ]);

    // Navegar a la página de edición
    $page = visit("/admin/grades/{$grade->id}/edit");
    $page->wait(2);
    $page->assertSee('Editar Grado');
    $page->assertSee('1er Año');

    // Modificar solo el nombre (más simple y seguro)
    $page->clear('[data-test="grade-name-input"]');
    $page->wait(0.2);
    $page->type('[data-test="grade-name-input"]', '1er Año (Modificado)');
    $page->wait(0.5);

    // Submit
    $page->click('[data-test="submit-button"]');
    $page->wait(5);

    // Verificar redirección (la URL ya no debe contener /edit)
    expect($page->url())->toContain('/admin/grades');
    expect($page->url())->not->toContain('/edit');

    // Verificar cambios en base de datos
    $this->assertDatabaseHas('grades', [
        'id' => $grade->id,
        'name' => '1er Año (Modificado)',
    ]);
});

// ============================================================================
// FASE 2.4: Editar Sección
// ============================================================================

test('admin puede editar una sección existente', function () {
    // Crear estructura completa con factory
    $academicYear = AcademicYear::factory()->create([
        'name' => '2024-2025',
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

    // Navegar a la página de edición
    $page = visit("/admin/sections/{$section->id}/edit");
    $page->wait(2);
    $page->assertSee('Editar Sección');

    // Modificar nombre
    $page->clear('[data-test="section-name-input"]');
    $page->wait(0.2);
    $page->type('[data-test="section-name-input"]', 'Sección B');
    $page->wait(0.5);

    // Submit
    $page->click('[data-test="submit-button"]');
    $page->wait(5);

    // Verificar redirección (la URL ya no debe contener /edit)
    expect($page->url())->toContain('/admin/sections');
    expect($page->url())->not->toContain('/edit');

    // Verificar cambios en base de datos
    $this->assertDatabaseHas('sections', [
        'id' => $section->id,
        'name' => 'Sección B',
    ]);
});

// ============================================================================
// FASE 2.5: Editar Usuario
// ============================================================================

test('admin puede editar un usuario existente', function () {
    // Crear usuario con factory
    $user = User::factory()->create([
        'cedula' => 'V-12345678',
        'name' => 'Juan Pérez',
        'email' => 'juan.perez@test.com',
        'phone' => '0412-1234567',
        'address' => 'Calle Principal #123',
    ]);
    $user->assignRole('alumno');

    // Navegar a la página de edición
    $page = visit("/admin/users/{$user->id}/edit");
    $page->wait(2);
    $page->assertSee('Editar Usuario');
    $page->assertSee('Juan Pérez');

    // Modificar solo el nombre (más simple y seguro) - usar #id en lugar de data-test
    $page->clear('#name');
    $page->wait(0.2);
    $page->type('#name', 'Juan Pérez Modificado');
    $page->wait(0.5);

    // Submit
    $page->click('button[type="submit"]');
    $page->wait(5);

    // Verificar redirección (la URL ya no debe contener /edit)
    expect($page->url())->toContain('/admin/users');
    expect($page->url())->not->toContain('/edit');

    // Verificar cambios en base de datos
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Juan Pérez Modificado',
    ]);
});
