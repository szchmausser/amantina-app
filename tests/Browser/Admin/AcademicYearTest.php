<?php

use App\Models\AcademicYear;
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
});

test('admin puede ver el listado de años académicos', function () {
    AcademicYear::factory()->count(2)->create();

    $this->visit('/admin/academic-years')
        ->wait(2)
        ->assertPathIs('/admin/academic-years')
        ->assertSee('Años Escolares')
        ->assertSee('Gestión de años escolares')
        ->assertNoJavaScriptErrors();
});

test('admin puede acceder al formulario de creación de año académico', function () {
    $this->visit('/admin/academic-years')
        ->wait(2)
        ->click('text=Nuevo Año Escolar')
        ->wait(2)
        ->assertPathIs('/admin/academic-years/create')
        ->assertSee('Nuevo Año Escolar')
        ->assertSee('Registra un nuevo ciclo académico')
        ->assertNoJavaScriptErrors();
});

test('admin puede crear un año académico', function () {
    $this->visit('/admin/academic-years/create')
        ->wait(2)
        ->assertSee('Nuevo Año Escolar')
        ->assertSee('Registra un nuevo ciclo académico')
        // Llenar el formulario
        ->type('#name', '2025-2026')
        ->wait(0.5)
        ->type('#start_date', '2025-09-01')
        ->wait(0.5)
        ->type('#end_date', '2026-07-15')
        ->wait(0.5)
        ->clear('#required_hours')
        ->type('#required_hours', '600')
        ->wait(0.5)
        // Hacer clic en el checkbox de is_active (es un button en shadcn/ui)
        ->click('button#is_active')
        ->wait(1)
        // Enviar el formulario
        ->click('button[type="submit"]')
        ->wait(5) // Esperar más tiempo para la redirección
        ->assertPathIs('/admin/academic-years')
        ->assertSee('Años Escolares')
        ->assertNoJavaScriptErrors();

    // Verificar que se creó en la base de datos
    $this->assertDatabaseHas('academic_years', [
        'name' => '2025-2026',
        'required_hours' => 600,
        'is_active' => true,
    ]);
});

test('admin puede ver el detalle de un año académico', function () {
    $year = AcademicYear::factory()->create(['name' => '2024-2025']);

    $this->visit("/admin/academic-years/{$year->id}")
        ->wait(2)
        ->assertPathIs("/admin/academic-years/{$year->id}")
        ->assertSee('2024-2025')
        ->assertSee('Detalles y configuración del año escolar')
        ->assertNoJavaScriptErrors();
});

test('admin puede acceder al formulario de edición de año académico', function () {
    $year = AcademicYear::factory()->create(['name' => '2024-2025']);

    $this->visit("/admin/academic-years/{$year->id}/edit")
        ->wait(2)
        ->assertPathIs("/admin/academic-years/{$year->id}/edit")
        ->assertSee('Editar Año Escolar')
        ->assertSee('2024-2025')
        ->assertNoJavaScriptErrors();
});

test('año académico activo se muestra destacado en el listado', function () {
    AcademicYear::factory()->create(['name' => '2023-2024', 'is_active' => false]);
    AcademicYear::factory()->create(['name' => '2024-2025', 'is_active' => true]);

    $this->visit('/admin/academic-years')
        ->wait(2)
        ->assertSee('2024-2025')
        ->assertSee('2023-2024')
        ->assertSee('Activo') // Badge del año activo
        ->assertNoJavaScriptErrors();
});

// ============================================================================
// TESTS EXHAUSTIVOS DE SEGURIDAD - BASADOS EN PERMISOS
// ============================================================================

// --- USUARIOS SIN PERMISO academic_years.view NO pueden VER ---

test('usuario sin permiso academic_years.view NO puede ver listado', function () {
    $usuario = User::factory()->create();
    $usuario->assignRole('alumno'); // alumno NO tiene academic_years.view

    $this->actingAs($usuario);

    $this->visit('/admin/academic-years')
        ->wait(2)
        ->assertSee('403');
});

test('usuario sin permiso academic_years.view NO puede ver detalle', function () {
    $usuario = User::factory()->create();
    $usuario->assignRole('profesor'); // profesor NO tiene academic_years.view

    $this->actingAs($usuario);

    $year = AcademicYear::factory()->create();

    $this->visit("/admin/academic-years/{$year->id}")
        ->wait(2)
        ->assertSee('403');
});

// --- USUARIOS SIN PERMISO academic_years.create NO pueden CREAR ---

test('usuario sin permiso academic_years.create NO puede acceder a formulario de creación', function () {
    $usuario = User::factory()->create();
    $usuario->assignRole('representante'); // representante NO tiene academic_years.create

    $this->actingAs($usuario);

    $this->visit('/admin/academic-years/create')
        ->wait(2)
        ->assertSee('403');
});

test('usuario sin permiso academic_years.create NO puede crear mediante POST', function () {
    $usuario = User::factory()->create();
    $usuario->assignRole('alumno'); // alumno NO tiene academic_years.create

    $this->actingAs($usuario);

    $response = $this->post('/admin/academic-years', [
        'name' => '2025-2026',
        'start_date' => '2025-09-01',
        'end_date' => '2026-07-15',
        'required_hours' => 600,
        'is_active' => true,
    ]);

    $response->assertStatus(403);

    $this->assertDatabaseMissing('academic_years', [
        'name' => '2025-2026',
    ]);
});

test('profesor sin permiso academic_years.create NO puede crear mediante POST', function () {
    $usuario = User::factory()->create();
    $usuario->assignRole('profesor'); // profesor NO tiene academic_years.create

    $this->actingAs($usuario);

    $response = $this->post('/admin/academic-years', [
        'name' => '2025-2026',
        'start_date' => '2025-09-01',
        'end_date' => '2026-07-15',
        'required_hours' => 600,
        'is_active' => true,
    ]);

    $response->assertStatus(403);

    $this->assertDatabaseMissing('academic_years', [
        'name' => '2025-2026',
    ]);
});

// --- USUARIOS SIN PERMISO academic_years.edit NO pueden EDITAR ---

test('usuario sin permiso academic_years.edit NO puede acceder a formulario de edición', function () {
    $usuario = User::factory()->create();
    $usuario->assignRole('alumno'); // alumno NO tiene academic_years.edit

    $this->actingAs($usuario);

    $year = AcademicYear::factory()->create();

    $this->visit("/admin/academic-years/{$year->id}/edit")
        ->wait(2)
        ->assertSee('403');
});

test('usuario sin permiso academic_years.edit NO puede editar mediante PUT', function () {
    $usuario = User::factory()->create();
    $usuario->assignRole('profesor'); // profesor NO tiene academic_years.edit

    $this->actingAs($usuario);

    $year = AcademicYear::factory()->create(['name' => 'Original']);

    $response = $this->put("/admin/academic-years/{$year->id}", [
        'name' => 'Modificado',
        'start_date' => $year->start_date,
        'end_date' => $year->end_date,
        'required_hours' => $year->required_hours,
        'is_active' => $year->is_active,
    ]);

    $response->assertStatus(403);

    $this->assertDatabaseHas('academic_years', [
        'id' => $year->id,
        'name' => 'Original',
    ]);
});

test('representante sin permiso academic_years.edit NO puede editar mediante PUT', function () {
    $usuario = User::factory()->create();
    $usuario->assignRole('representante'); // representante NO tiene academic_years.edit

    $this->actingAs($usuario);

    $year = AcademicYear::factory()->create(['name' => 'Original']);

    $response = $this->put("/admin/academic-years/{$year->id}", [
        'name' => 'Modificado',
        'start_date' => $year->start_date,
        'end_date' => $year->end_date,
        'required_hours' => $year->required_hours,
        'is_active' => $year->is_active,
    ]);

    $response->assertStatus(403);

    $this->assertDatabaseHas('academic_years', [
        'id' => $year->id,
        'name' => 'Original',
    ]);
});

// --- USUARIOS SIN PERMISO academic_years.delete NO pueden ELIMINAR ---

test('usuario sin permiso academic_years.delete NO puede eliminar mediante DELETE', function () {
    $usuario = User::factory()->create();
    $usuario->assignRole('alumno'); // alumno NO tiene academic_years.delete

    $this->actingAs($usuario);

    $year = AcademicYear::factory()->create();

    $response = $this->delete("/admin/academic-years/{$year->id}");

    $response->assertStatus(403);

    $this->assertDatabaseHas('academic_years', [
        'id' => $year->id,
    ]);
});

test('profesor sin permiso academic_years.delete NO puede eliminar mediante DELETE', function () {
    $usuario = User::factory()->create();
    $usuario->assignRole('profesor'); // profesor NO tiene academic_years.delete

    $this->actingAs($usuario);

    $year = AcademicYear::factory()->create();

    $response = $this->delete("/admin/academic-years/{$year->id}");

    $response->assertStatus(403);

    $this->assertDatabaseHas('academic_years', [
        'id' => $year->id,
    ]);
});

test('representante sin permiso academic_years.delete NO puede eliminar mediante DELETE', function () {
    $usuario = User::factory()->create();
    $usuario->assignRole('representante'); // representante NO tiene academic_years.delete

    $this->actingAs($usuario);

    $year = AcademicYear::factory()->create();

    $response = $this->delete("/admin/academic-years/{$year->id}");

    $response->assertStatus(403);

    $this->assertDatabaseHas('academic_years', [
        'id' => $year->id,
    ]);
});

// --- USUARIOS CON PERMISOS SÍ pueden realizar acciones ---

test('usuario CON permiso academic_years.view SÍ puede ver listado', function () {
    // Admin tiene todos los permisos
    $this->visit('/admin/academic-years')
        ->wait(2)
        ->assertPathIs('/admin/academic-years')
        ->assertSee('Años Escolares');
});

test('usuario CON permiso academic_years.create SÍ puede crear', function () {
    // Admin tiene todos los permisos
    $response = $this->post('/admin/academic-years', [
        'name' => '2026-2027',
        'start_date' => '2026-09-01',
        'end_date' => '2027-07-15',
        'required_hours' => 600,
        'is_active' => false,
    ]);

    $response->assertStatus(302); // Redirección exitosa

    $this->assertDatabaseHas('academic_years', [
        'name' => '2026-2027',
    ]);
});

test('usuario CON permiso academic_years.edit SÍ puede editar', function () {
    // Admin tiene todos los permisos
    $year = AcademicYear::factory()->create(['name' => 'Original']);

    $response = $this->put("/admin/academic-years/{$year->id}", [
        'name' => 'Modificado por Admin',
        'start_date' => $year->start_date,
        'end_date' => $year->end_date,
        'required_hours' => $year->required_hours,
        'is_active' => $year->is_active,
    ]);

    $response->assertStatus(302); // Redirección exitosa

    $this->assertDatabaseHas('academic_years', [
        'id' => $year->id,
        'name' => 'Modificado por Admin',
    ]);
});

test('usuario CON permiso academic_years.delete SÍ puede eliminar', function () {
    // Admin tiene todos los permisos
    $year = AcademicYear::factory()->create();

    $response = $this->delete("/admin/academic-years/{$year->id}");

    $response->assertStatus(302); // Redirección exitosa

    $this->assertDatabaseMissing('academic_years', [
        'id' => $year->id,
        'deleted_at' => null,
    ]);
});
