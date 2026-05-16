<?php

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\User;
use Database\Seeders\FieldSessionStatusSeeder;
use Database\Seeders\GradeDefinitionSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SectionDefinitionSeeder;
use Database\Seeders\TermTypeSeeder;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Pest\Browser\Browsable;

uses(DatabaseTruncation::class);
uses(Browsable::class);

/**
 * FASE 5: TESTS DE VALIDACIÓN FRONTEND
 *
 * Estos tests verifican que las validaciones del lado del servidor se reflejan
 * correctamente en la interfaz de usuario mediante mensajes de error visibles:
 * - Campos requeridos muestran error
 * - Validación de formato de fechas
 * - Validación de rangos numéricos
 * - Validación de emails
 * - Validación de contraseñas
 *
 * PRINCIPIO 7 (browser-testing): El test PRIMERO verifica que el mensaje de
 * error aparece en pantalla, y SECUNDARIAMENTE verifica que la URL no cambió.
 *
 * NOTA TÉCNICA: Algunos formularios usan validación HTML5 (required, type="email",
 * type="number") que impide el envío del formulario al servidor. Para probar la
 * validación del lado del servidor (Laravel/Inertia), se usa script() para
 * deshabilitar la validación HTML5 (noValidate) antes de enviar, lo que permite
 * que Inertia reciba los errores del servidor y los muestre en pantalla.
 */
beforeEach(function () {
    // Seed roles, permissions, term types, and field session statuses
    $this->seed(RoleAndPermissionSeeder::class);
    $this->seed(TermTypeSeeder::class);
    $this->seed(FieldSessionStatusSeeder::class);
    $this->seed(GradeDefinitionSeeder::class);
    $this->seed(SectionDefinitionSeeder::class);

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

    // Deshabilitar validación HTML5 para probar la validación del servidor
    $page->script('document.querySelector("form").noValidate = true');

    // Intentar submit sin llenar campos
    $page->click('button[type="submit"]');
    $page->wait(2);

    // PRIMARIO: verificar que el mensaje de error aparece en pantalla
    $page->assertSee('The name field is required.');

    // SECUNDARIO: verificar que NO redirecciona (se queda en el formulario)
    expect($page->url())->toContain('/admin/academic-years/create');
});

it('formulario de lapso muestra error cuando campos requeridos están vacíos', function () {
    // Crear año escolar primero
    $academicYear = AcademicYear::factory()->create();

    $page = visit('/admin/school-terms/create');
    $page->wait(2);

    $page->assertSee('Nuevo Lapso Académico');

    // Seleccionar año escolar pero NO llenar fechas
    // NOTA: term_type_id tiene un valor por defecto en el formulario (primer tipo),
    // por lo que los campos requeridos que faltan son start_date y end_date
    $page->click('[data-test="academic-year-select-trigger"]');
    $page->wait(1);
    $page->click('[role="option"]:has-text("'.$academicYear->name.'")');
    $page->wait(1);

    // Deshabilitar validación HTML5 para probar la validación del servidor
    $page->script('document.querySelector("form").noValidate = true');

    // Intentar submit sin llenar fechas
    $page->click('button[type="submit"]');
    $page->wait(2);

    // PRIMARIO: verificar que el mensaje de error aparece en pantalla
    $page->assertSee('The start date field is required.');

    // SECUNDARIO: verificar que NO redirecciona (se queda en el formulario)
    expect($page->url())->toContain('/admin/school-terms/create');
});

it('formulario de grado muestra error cuando nombre está vacío', function () {
    $academicYear = AcademicYear::factory()->create();

    $page = visit('/admin/grades/create');
    $page->wait(2);

    $page->assertSee('Nuevo Grado');

    // Seleccionar año escolar pero NO seleccionar definición de grado
    $page->click('[data-test="academic-year-select-trigger"]');
    $page->wait(1);
    $page->click('[role="option"]:has-text("'.$academicYear->name.'")');
    $page->wait(1);

    // Llenar orden pero NO definición de grado
    $page->type('[data-test="grade-order-input"]', '1');

    // Intentar submit
    $page->click('button[type="submit"]');
    $page->wait(2);

    // PRIMARIO: verificar que el mensaje de error aparece en pantalla
    $page->assertSee('The grade definition id field is required.');

    // SECUNDARIO: verificar que NO redirecciona
    expect($page->url())->toContain('/admin/grades/create');
});

it('formulario de sección muestra error cuando nombre está vacío', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = Grade::factory()->create(['academic_year_id' => $academicYear->id]);

    $page = visit('/admin/sections/create');
    $page->wait(2);

    $page->assertSee('Nueva Sección');

    // Seleccionar año y grado pero NO seleccionar definición de sección
    $page->click('[data-test="academic-year-select-trigger"]');
    $page->wait(1);
    $page->click('[role="option"]:has-text("'.$academicYear->name.'")');
    $page->wait(1);

    $page->click('[data-test="grade-select-trigger"]');
    $page->wait(1);
    $page->click('[role="option"]:has-text("'.$grade->name.'")');
    $page->wait(1);

    // Intentar submit sin definición de sección
    $page->click('button[type="submit"]');
    $page->wait(2);

    // PRIMARIO: verificar que el mensaje de error aparece en pantalla
    $page->assertSee('The section definition id field is required.');

    // SECUNDARIO: verificar que NO redirecciona
    expect($page->url())->toContain('/admin/sections/create');
});

it('formulario de usuario muestra error cuando campos requeridos están vacíos', function () {
    $page = visit('/admin/users/create');
    $page->wait(2);

    $page->assertSee('Nuevo Usuario');

    // Intentar submit sin llenar nada
    $page->click('button[type="submit"]');
    $page->wait(2);

    // PRIMARIO: verificar que los mensajes de error aparecen en pantalla
    $page->assertSee('The name field is required.');
    $page->assertSee('The email field is required.');

    // SECUNDARIO: verificar que NO redirecciona (se queda en el formulario)
    expect($page->url())->toContain('/admin/users/create');
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

    // Deshabilitar validación HTML5 para probar la validación del servidor
    $page->script('document.querySelector("form").noValidate = true');

    // Intentar submit
    $page->click('button[type="submit"]');
    $page->wait(2);

    // PRIMARIO: verificar que el mensaje de error de validación aparece en pantalla
    $page->assertSee('The end date field must be a date after start date.');

    // SECUNDARIO: verificar que se queda en el formulario (no redirige)
    expect($page->url())->toContain('/admin/school-terms/create');
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

    // Deshabilitar validación HTML5 para probar la validación del servidor
    $page->script('document.querySelector("form").noValidate = true');

    // Intentar submit
    $page->click('button[type="submit"]');
    $page->wait(2);

    // PRIMARIO: verificar que el mensaje de error de validación aparece en pantalla
    $page->assertSee('The end date field must be a date after start date.');

    // SECUNDARIO: verificar que se queda en el formulario (no redirige)
    expect($page->url())->toContain('/admin/academic-years/create');
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

    // Deshabilitar validación HTML5 (type="email" bloquea envíos con email inválido)
    $page->script('document.querySelector("form").noValidate = true');

    // Intentar submit
    $page->click('button[type="submit"]');
    $page->wait(2);

    // PRIMARIO: verificar que el mensaje de error de email aparece en pantalla
    $page->assertSee('The email field must be a valid email address.');

    // SECUNDARIO: verificar que NO redirecciona (se queda en el formulario)
    expect($page->url())->toContain('/admin/users/create');
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
    $page->wait(2);

    // PRIMARIO: verificar que el mensaje de error de confirmación aparece en pantalla
    $page->assertSee('The password field confirmation does not match.');

    // SECUNDARIO: verificar que NO redirecciona (se queda en el formulario)
    expect($page->url())->toContain('/admin/users/create');
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

    // Deshabilitar validación HTML5 (minLength en input password puede bloquear)
    $page->script('document.querySelector("form").noValidate = true');

    // Intentar submit
    $page->click('button[type="submit"]');
    $page->wait(2);

    // PRIMARIO: verificar que el mensaje de error de longitud mínima aparece en pantalla
    $page->assertSee('The password field must be at least 8 characters.');

    // SECUNDARIO: verificar que NO redirecciona (se queda en el formulario)
    expect($page->url())->toContain('/admin/users/create');
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

    // Select grade definition (dropdown instead of text input)
    $page->click('[data-test="grade-definition-select-trigger"]');
    $page->wait(0.5);
    $page->click('[role="option"]:has-text("1er Año")');
    $page->wait(0.5);

    // Ingresar orden inválido (-1 no cumple min:1)
    $page->type('[data-test="grade-order-input"]', '-1');

    // Deshabilitar validación HTML5 (type="number" con min puede bloquear envío)
    $page->script('document.querySelector("form").noValidate = true');

    // Intentar submit
    $page->click('button[type="submit"]');
    $page->wait(2);

    // PRIMARIO: verificar que el mensaje de error aparece en pantalla
    $page->assertSee('The order field must be at least 1.');

    // SECUNDARIO: verificar que se queda en el formulario (no redirige)
    expect($page->url())->toContain('/admin/grades/create');
});
