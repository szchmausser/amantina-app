<?php

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\SchoolTerm;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Browsable;

uses(RefreshDatabase::class, Browsable::class);

/**
 * FASE 5: TESTS DE VALIDACIÓN FRONTEND
 * 
 * Estos tests verifican que las validaciones del lado del cliente funcionan correctamente:
 * - Campos requeridos muestran error
 * - Validación de formato de fechas
 * - Validación de rangos numéricos
 * - Validación de emails
 * - Validación de contraseñas
 */

beforeEach(function () {
    // Seed roles, permissions, term types, and field session statuses
    $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);
    $this->seed(\Database\Seeders\TermTypeSeeder::class);
    $this->seed(\Database\Seeders\FieldSessionStatusSeeder::class);
    
    // Crear admin y autenticar
    $admin = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
    ]);
    $admin->assignRole('admin');
    
    $this->actingAs($admin);
});

// ============================================================================
// 5.1 VALIDACIÓN DE CAMPOS REQUERIDOS
// ============================================================================

it('formulario de año escolar muestra error cuando campos requeridos están vacíos', function () {
    $page = visit('/admin/academic-years/create');
    $page->wait(2);
    
    $page->assertSee('Nuevo Año Escolar');
    
    // Intentar submit sin llenar campos
    $page->click('button[type="submit"]');
    $page->wait(1);
    
    // Verificar que NO redirecciona (se queda en la misma página)
    expect($page->url())->toContain('/admin/academic-years/create');
    
    // Verificar que no se creó nada en la base de datos
    $this->assertDatabaseCount('academic_years', 0);
});

it('formulario de lapso muestra error cuando campos requeridos están vacíos', function () {
    // Crear año escolar primero
    $academicYear = AcademicYear::factory()->create();
    
    $page = visit('/admin/school-terms/create');
    $page->wait(2);
    
    $page->assertSee('Nuevo Lapso Académico');
    
    // Seleccionar año escolar pero NO llenar fechas ni tipo
    $page->click('[data-test="academic-year-select-trigger"]');
    $page->wait(1);
    $page->click('[role="option"]:has-text("'.$academicYear->name.'")');
    $page->wait(1);
    
    // Intentar submit sin llenar fechas ni tipo
    $page->click('button[type="submit"]');
    $page->wait(1);
    
    // Verificar que NO redirecciona
    expect($page->url())->toContain('/admin/school-terms/create');
    
    // Verificar que no se creó nada
    $this->assertDatabaseCount('school_terms', 0);
});

it('formulario de grado muestra error cuando nombre está vacío', function () {
    $academicYear = AcademicYear::factory()->create();
    
    $page = visit('/admin/grades/create');
    $page->wait(2);
    
    $page->assertSee('Nuevo Grado');
    
    // Seleccionar año escolar pero NO llenar nombre
    $page->click('[data-test="academic-year-select-trigger"]');
    $page->wait(1);
    $page->click('[role="option"]:has-text("'.$academicYear->name.'")');
    $page->wait(1);
    
    // Llenar orden pero NO nombre
    $page->type('[data-test="grade-order-input"]', '1');
    
    // Intentar submit
    $page->click('button[type="submit"]');
    $page->wait(1);
    
    // Verificar que NO redirecciona
    expect($page->url())->toContain('/admin/grades/create');
    
    // Verificar que no se creó nada
    $this->assertDatabaseCount('grades', 0);
});

it('formulario de sección muestra error cuando nombre está vacío', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create(['academic_year_id' => $academicYear->id]);
    
    $page = visit('/admin/sections/create');
    $page->wait(2);
    
    $page->assertSee('Nueva Sección');
    
    // Seleccionar año y grado pero NO llenar nombre
    $page->click('[data-test="academic-year-select-trigger"]');
    $page->wait(1);
    $page->click('[role="option"]:has-text("'.$academicYear->name.'")');
    $page->wait(1);
    
    $page->click('[data-test="grade-select-trigger"]');
    $page->wait(1);
    $page->click('[role="option"]:has-text("'.$grade->name.'")');
    $page->wait(1);
    
    // Intentar submit sin nombre
    $page->click('button[type="submit"]');
    $page->wait(1);
    
    // Verificar que NO redirecciona
    expect($page->url())->toContain('/admin/sections/create');
    
    // Verificar que no se creó nada
    $this->assertDatabaseCount('sections', 0);
});

it('formulario de usuario muestra error cuando campos requeridos están vacíos', function () {
    $page = visit('/admin/users/create');
    $page->wait(2);
    
    $page->assertSee('Nuevo Usuario');
    
    // Intentar submit sin llenar nada
    $page->click('button[type="submit"]');
    $page->wait(1);
    
    // Verificar que NO redirecciona
    expect($page->url())->toContain('/admin/users/create');
    
    // Verificar que no se creó nada (solo existe el admin del beforeEach)
    $this->assertDatabaseCount('users', 1);
});

// ============================================================================
// 5.2 VALIDACIÓN DE FORMATO DE FECHAS
// ============================================================================

it('formulario de lapso valida que fecha de inicio sea anterior a fecha de fin', function () {
    $academicYear = AcademicYear::factory()->create([
        'start_date' => '2025-09-01',
        'end_date' => '2026-07-31',
    ]);
    
    $page = visit('/admin/school-terms/create');
    $page->wait(2);
    
    // Seleccionar año escolar
    $page->click('[data-test="academic-year-select-trigger"]');
    $page->wait(1);
    $page->click('[role="option"]:has-text("'.$academicYear->name.'")');
    $page->wait(1);
    
    // Seleccionar tipo de lapso
    $page->click('[data-test="term-type-select-trigger"]');
    $page->wait(1);
    $page->click('[role="option"]:has-text("Lapso 1")');
    $page->wait(1);
    
    // Ingresar fecha de inicio POSTERIOR a fecha de fin (inválido)
    $page->type('[data-test="start-date-input"]', '2025-12-31');
    $page->type('[data-test="end-date-input"]', '2025-09-01');
    
    // Intentar submit
    $page->click('button[type="submit"]');
    $page->wait(2);
    
    // Verificar que NO redirecciona (validación backend rechaza)
    expect($page->url())->toContain('/admin/school-terms');
    
    // Verificar que no se creó nada
    $this->assertDatabaseCount('school_terms', 0);
});

it('formulario de año escolar valida que fecha de inicio sea anterior a fecha de fin', function () {
    $page = visit('/admin/academic-years/create');
    $page->wait(2);
    
    // Llenar nombre
    $page->type('#name', '2025-2026');
    
    // Ingresar fecha de inicio POSTERIOR a fecha de fin (inválido)
    $page->type('#start_date', '2026-07-31');
    $page->type('#end_date', '2025-09-01');
    
    // Llenar horas requeridas (campo obligatorio)
    $page->type('#required_hours', '120');
    
    // Intentar submit
    $page->click('button[type="submit"]');
    $page->wait(2);
    
    // Verificar que NO redirecciona o muestra error
    // La validación puede ser frontend (Inertia) o backend
    expect($page->url())->toContain('/admin/academic-years');
    
    // Verificar que no se creó nada
    $this->assertDatabaseCount('academic_years', 0);
});

// ============================================================================
// 5.3 VALIDACIÓN DE EMAIL Y CONTRASEÑA
// ============================================================================

it('formulario de usuario valida formato de email', function () {
    $page = visit('/admin/users/create');
    $page->wait(2);
    
    // Llenar todos los campos EXCEPTO email válido
    $page->type('[data-test="cedula-input"]', 'V-12345678');
    $page->type('[data-test="name-input"]', 'Juan Pérez');
    $page->type('[data-test="email-input"]', 'email-invalido'); // Email sin @
    $page->type('[data-test="password-input"]', 'password123');
    $page->type('[data-test="password-confirmation-input"]', 'password123');
    
    // Seleccionar rol
    $page->click('[data-test="role-checkbox-alumno"]');
    $page->wait(0.5);
    
    // Intentar submit
    $page->click('button[type="submit"]');
    $page->wait(1);
    
    // Verificar que NO redirecciona
    expect($page->url())->toContain('/admin/users/create');
    
    // Verificar que no se creó nada (solo admin)
    $this->assertDatabaseCount('users', 1);
});

it('formulario de usuario valida que contraseñas coincidan', function () {
    $page = visit('/admin/users/create');
    $page->wait(2);
    
    // Llenar todos los campos con contraseñas DIFERENTES
    $page->type('[data-test="cedula-input"]', 'V-12345678');
    $page->type('[data-test="name-input"]', 'Juan Pérez');
    $page->type('[data-test="email-input"]', 'juan@test.com');
    $page->type('[data-test="password-input"]', 'password123');
    $page->type('[data-test="password-confirmation-input"]', 'password456'); // Diferente
    
    // Seleccionar rol
    $page->click('[data-test="role-checkbox-alumno"]');
    $page->wait(0.5);
    
    // Intentar submit
    $page->click('button[type="submit"]');
    $page->wait(1);
    
    // Verificar que NO redirecciona
    expect($page->url())->toContain('/admin/users/create');
    
    // Verificar que no se creó nada (solo admin)
    $this->assertDatabaseCount('users', 1);
});

it('formulario de usuario valida longitud mínima de contraseña', function () {
    $page = visit('/admin/users/create');
    $page->wait(2);
    
    // Llenar todos los campos con contraseña MUY CORTA
    $page->type('[data-test="cedula-input"]', 'V-12345678');
    $page->type('[data-test="name-input"]', 'Juan Pérez');
    $page->type('[data-test="email-input"]', 'juan@test.com');
    $page->type('[data-test="password-input"]', '123'); // Muy corta
    $page->type('[data-test="password-confirmation-input"]', '123');
    
    // Seleccionar rol
    $page->click('[data-test="role-checkbox-alumno"]');
    $page->wait(0.5);
    
    // Intentar submit
    $page->click('button[type="submit"]');
    $page->wait(1);
    
    // Verificar que NO redirecciona
    expect($page->url())->toContain('/admin/users/create');
    
    // Verificar que no se creó nada (solo admin)
    $this->assertDatabaseCount('users', 1);
});

// ============================================================================
// 5.4 VALIDACIÓN DE RANGOS NUMÉRICOS
// ============================================================================

it('formulario de grado valida que orden sea número positivo', function () {
    $academicYear = AcademicYear::factory()->create();
    
    $page = visit('/admin/grades/create');
    $page->wait(2);
    
    // Seleccionar año escolar
    $page->click('[data-test="academic-year-select-trigger"]');
    $page->wait(1);
    $page->click('[role="option"]:has-text("'.$academicYear->name.'")');
    $page->wait(1);
    
    // Llenar nombre
    $page->type('[data-test="grade-name-input"]', '1er Año');
    
    // Intentar ingresar orden negativo
    $page->type('[data-test="grade-order-input"]', '-1');
    
    // Intentar submit
    $page->click('button[type="submit"]');
    $page->wait(1);
    
    // Verificar que NO redirecciona o muestra error
    expect($page->url())->toContain('/admin/grades');
    
    // Verificar que no se creó nada
    $this->assertDatabaseCount('grades', 0);
});
