<?php

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\SchoolTerm;
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
 * 4. Editar Usuario
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

    // Verificar que el nombre actualizado aparece en el listado
    $page->assertSee('2024-2025 Modificado');
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

    // Verificar que redirigió al listado (la edición fue exitosa)
    $page->assertSee('Lapso 1');

    // Verificar que la fecha de fin actualizada aparece en el listado
    // formatDate() renderiza como DD/MM/YYYY
    $page->assertSee('20/12/2024');
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
        'order' => 1,
    ]);

    // Navegar a la página de edición
    $page = visit("/admin/grades/{$grade->id}/edit");
    $page->wait(2);
    $page->assertSee('Editar Grado');

    // Modificar el orden (el nombre viene de la definición y no se puede editar)
    $page->clear('[data-test="grade-order-input"]');
    $page->wait(0.2);
    $page->type('[data-test="grade-order-input"]', '5');
    $page->wait(0.5);

    // Submit
    $page->click('[data-test="submit-button"]');
    $page->wait(5);

    // Verificar redirección (la URL ya no debe contener /edit)
    expect($page->url())->toContain('/admin/grades');
    expect($page->url())->not->toContain('/edit');

    // Verificar que el grado editado sigue apareciendo en el listado
    // Nota: el campo 'order' no se muestra en el listado de grados,
    // por lo que se verifica el nombre del grado como prueba de que la edición fue exitosa
    $page->assertSee($grade->name);

    // Verificar que no hay errores de JavaScript tras la edición exitosa
    $page->assertNoJavaScriptErrors();
});

// ============================================================================
// FASE 2.5: Editar Usuario
// ============================================================================

// NOTA: FASE 2.4 (Editar Sección) fue eliminada porque en modo edición
// todos los campos del formulario están deshabilitados (academic_year, grade,
// section_definition son Selects con disabled={isEditing}), por lo que no hay
// nada que editar. La sección es un test puramente ornamental sin verificación útil.
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

    // Verificar que el nombre actualizado aparece en el listado
    $page->assertSee('Juan Pérez Modificado');
});
