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
 * Happy Path: Admin Navigation Flow
 *
 * Este test verifica que los administradores pueden navegar correctamente
 * por el sistema y usar los filtros disponibles.
 * Cubre la Fase 4 del plan de cobertura de tests browser.
 *
 * Tests incluidos:
 * 1. Navegación desde Dashboard a todos los módulos
 * 2. Búsqueda de Usuarios por nombre o cédula
 * 3. Filtros por Año Escolar en diferentes módulos
 * 4. Filtros por Grado y Sección
 */
beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->seed(TermTypeSeeder::class);
    $this->seed(FieldSessionStatusSeeder::class);

    $this->admin = User::factory()->create([
        'email' => 'admin@navtest.com',
        'password' => bcrypt('password'),
    ]);
    $this->admin->assignRole('admin');

    $this->actingAs($this->admin);
});

// ============================================================================
// FASE 4.1: Navegación desde Dashboard
// ============================================================================

test('admin puede navegar desde dashboard a todos los módulos', function () {
    // Navegar al dashboard
    $page = visit('/dashboard');
    $page->wait(2);

    // Verificar que el dashboard carga
    expect($page->url())->toContain('/dashboard');

    // Click en "Información Académica" en el sidebar
    $page->click('text="Información Académica"');
    $page->wait(2);
    expect($page->url())->toContain('/admin/academic-info');
    $page->assertSee('Información Académica');

    // Click en "Gestión de Usuarios" en el sidebar
    $page->click('text="Gestión de Usuarios"');
    $page->wait(2);
    expect($page->url())->toContain('/admin/users');
    $page->assertSee('Gestión de Usuarios');

    // Navegar a Años Escolares desde el menú o URL directa
    $page = visit('/admin/academic-years');
    $page->wait(2);
    expect($page->url())->toContain('/admin/academic-years');
    $page->assertSee('Años Escolares');

    // Navegar a Lapsos
    $page = visit('/admin/school-terms');
    $page->wait(2);
    expect($page->url())->toContain('/admin/school-terms');
    $page->assertSee('Lapsos Académicos');

    // Navegar a Grados
    $page = visit('/admin/grades');
    $page->wait(2);
    expect($page->url())->toContain('/admin/grades');
    $page->assertSee('Grados');

    // Navegar a Secciones
    $page = visit('/admin/sections');
    $page->wait(2);
    expect($page->url())->toContain('/admin/sections');
    $page->assertSee('Secciones');
});

// ============================================================================
// FASE 4.2: Búsqueda de Usuarios
// ============================================================================

test('admin puede buscar usuarios por nombre o cédula', function () {
    // Crear varios usuarios con factory
    $user1 = User::factory()->create([
        'cedula' => 'V-11111111',
        'name' => 'Ana García',
        'email' => 'ana.garcia@test.com',
    ]);
    $user1->assignRole('alumno');

    $user2 = User::factory()->create([
        'cedula' => 'V-22222222',
        'name' => 'Carlos Rodríguez',
        'email' => 'carlos.rodriguez@test.com',
    ]);
    $user2->assignRole('alumno');

    $user3 = User::factory()->create([
        'cedula' => 'V-33333333',
        'name' => 'María López',
        'email' => 'maria.lopez@test.com',
    ]);
    $user3->assignRole('profesor');

    // Navegar a la página de usuarios
    $page = visit('/admin/users');
    $page->wait(2);

    // Verificar que todos los usuarios aparecen inicialmente
    $page->assertSee('Ana García');
    $page->assertSee('Carlos Rodríguez');
    $page->assertSee('María López');

    // Buscar por nombre (Ana)
    $page->type('[data-test="search-input"]', 'Ana');
    $page->wait(1);
    $page->assertSee('Ana García');
    $page->assertDontSee('Carlos Rodríguez');
    $page->assertDontSee('María López');

    // Limpiar búsqueda
    $page->fill('[data-test="search-input"]', '');
    $page->wait(1);

    // Buscar por cédula (V-22222222)
    $page->type('[data-test="search-input"]', 'V-22222222');
    $page->wait(1);
    $page->assertSee('Carlos Rodríguez');
    $page->assertDontSee('Ana García');
    $page->assertDontSee('María López');

    // Limpiar búsqueda
    $page->fill('[data-test="search-input"]', '');
    $page->wait(1);

    // Buscar por apellido (López)
    $page->type('[data-test="search-input"]', 'López');
    $page->wait(1);
    $page->assertSee('María López');
    $page->assertDontSee('Ana García');
    $page->assertDontSee('Carlos Rodríguez');
});

// ============================================================================
// FASE 4.3: Filtros por Año Escolar
// ============================================================================

test('admin puede filtrar entidades por año escolar', function () {
    // Crear dos años escolares
    $year2024 = AcademicYear::factory()->create([
        'name' => '2024-2025',
        'start_date' => '2024-09-01',
        'end_date' => '2025-07-31',
        'is_active' => false,
    ]);

    $year2025 = AcademicYear::factory()->create([
        'name' => '2025-2026',
        'start_date' => '2025-09-01',
        'end_date' => '2026-07-31',
        'is_active' => true, // Este es el activo
    ]);

    // Crear lapsos para cada año
    $termType = TermType::where('name', 'Lapso 1')->first();

    $term2024 = SchoolTerm::factory()->create([
        'academic_year_id' => $year2024->id,
        'term_type_id' => $termType->id,
        'start_date' => '2024-09-01',
        'end_date' => '2024-12-15',
    ]);

    $term2025 = SchoolTerm::factory()->create([
        'academic_year_id' => $year2025->id,
        'term_type_id' => $termType->id,
        'start_date' => '2025-09-01',
        'end_date' => '2025-12-15',
    ]);

    // Crear grados para cada año
    $grade2024 = Grade::factory()->create([
        'academic_year_id' => $year2024->id,
        'name' => '1er Año (2024)',
        'order' => 1,
    ]);

    $grade2025 = Grade::factory()->create([
        'academic_year_id' => $year2025->id,
        'name' => '1er Año (2025)',
        'order' => 1,
    ]);

    // Crear secciones para cada grado
    $section2024 = Section::factory()->create([
        'grade_id' => $grade2024->id,
        'name' => 'Sección A (2024)',
    ]);

    $section2025 = Section::factory()->create([
        'grade_id' => $grade2025->id,
        'name' => 'Sección A (2025)',
    ]);

    // ========== FILTRAR LAPSOS ==========
    $page = visit('/admin/school-terms');
    $page->wait(2);

    // Por defecto muestra el año activo (2025-2026)
    $page->assertSee('2025-2026');
    $page->assertDontSee('2024-2025'); // No debe aparecer porque no es el año activo

    // Cambiar filtro a año 2024-2025
    $page->click('[data-test="academic-year-filter-trigger"]');
    $page->wait(1);
    $page->click('[role="option"]:has-text("2024-2025")');
    $page->wait(2);

    // Verificar que ahora solo aparece el lapso de 2024
    $page->assertSee('2024-2025');
    $page->assertDontSee('2025-2026');

    // ========== FILTRAR GRADOS ==========
    $page = visit('/admin/grades');
    $page->wait(2);

    // Por defecto muestra el año activo (2025-2026)
    $page->assertSee('1er Año (2025)');
    $page->assertDontSee('1er Año (2024)');

    // Cambiar filtro a año 2024-2025
    $page->click('[data-test="academic-year-filter-trigger"]');
    $page->wait(1);
    $page->click('[role="option"]:has-text("2024-2025")');
    $page->wait(2);

    // Verificar que ahora solo aparece el grado de 2024
    $page->assertSee('1er Año (2024)');
    $page->assertDontSee('1er Año (2025)');

    // ========== FILTRAR SECCIONES ==========
    $page = visit('/admin/sections');
    $page->wait(2);

    // Por defecto muestra el año activo (2025-2026)
    $page->assertSee('Sección A (2025)');
    $page->assertDontSee('Sección A (2024)');

    // Cambiar filtro a año 2024-2025
    $page->click('[data-test="academic-year-filter-trigger"]');
    $page->wait(1);
    $page->click('[role="option"]:has-text("2024-2025")');
    $page->wait(2);

    // Verificar que ahora solo aparece la sección de 2024
    $page->assertSee('Sección A (2024)');
    $page->assertDontSee('Sección A (2025)');
});

// ============================================================================
// FASE 4.4: Filtros por Grado y Sección (Cascada)
// ============================================================================

test('admin puede filtrar por grado y sección en cascada', function () {
    // Crear estructura completa
    $academicYear = AcademicYear::factory()->create([
        'name' => '2025-2026',
        'start_date' => '2025-09-01',
        'end_date' => '2026-07-31',
        'is_active' => true,
    ]);

    $grade1 = Grade::factory()->create([
        'academic_year_id' => $academicYear->id,
        'name' => '1er Año',
        'order' => 1,
    ]);

    $grade2 = Grade::factory()->create([
        'academic_year_id' => $academicYear->id,
        'name' => '2do Año',
        'order' => 2,
    ]);

    $section1A = Section::factory()->create([
        'grade_id' => $grade1->id,
        'name' => 'Sección A',
    ]);

    $section1B = Section::factory()->create([
        'grade_id' => $grade1->id,
        'name' => 'Sección B',
    ]);

    $section2A = Section::factory()->create([
        'grade_id' => $grade2->id,
        'name' => 'Sección A',
    ]);

    // Navegar a la página de secciones
    $page = visit('/admin/sections');
    $page->wait(2);

    // Verificar que todas las secciones aparecen inicialmente
    $page->assertSee('1er Año');
    $page->assertSee('2do Año');

    // Filtrar por grado "1er Año"
    $page->click('[data-test="grade-filter-trigger"]');
    $page->wait(2); // Aumentado wait para que el dropdown se abra completamente
    // Verificar que el dropdown está abierto
    $page->assertSee('1er Año'); // El texto debe estar visible en el dropdown
    // Usar un selector más específico para el item del dropdown
    $page->click('[role="option"]:has-text("1er Año")');
    $page->wait(3); // Aumentado wait para que el filtro se aplique

    // Verificar que solo aparecen las secciones de 1er Año
    $page->assertSee('1er Año');
    $page->assertDontSee('2do Año');

    // Limpiar filtros (si existe el botón)
    // Navegar de nuevo para resetear
    $page = visit('/admin/sections');
    $page->wait(2);

    // Filtrar por grado "2do Año"
    $page->click('[data-test="grade-filter-trigger"]');
    $page->wait(2); // Aumentado wait para que el dropdown se abra completamente
    // Verificar que el dropdown está abierto antes de hacer click
    $page->assertSee('2do Año'); // El texto debe estar visible en el dropdown
    $page->click('[role="option"]:has-text("2do Año")');
    $page->wait(3); // Aumentado wait para que el filtro se aplique

    // Verificar que solo aparecen las secciones de 2do Año
    $page->assertSee('2do Año');
    $page->assertDontSee('1er Año');
});
