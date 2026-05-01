<?php

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

test('admin puede ver el listado de usuarios', function () {
    User::factory()->count(3)->create();

    $this->visit('/admin/users')
        ->wait(2)
        ->assertPathIs('/admin/users')
        ->assertSee('Usuarios')
        ->assertSee('Administra las cuentas de usuario')
        ->assertNoJavaScriptErrors();
});

test('admin puede acceder al formulario de creación de usuario', function () {
    $this->visit('/admin/users')
        ->wait(2)
        ->click('text=Nuevo Usuario')
        ->wait(2)
        ->assertPathIs('/admin/users/create')
        ->assertSee('Crear')
        ->assertNoJavaScriptErrors();
});

test('admin puede buscar usuarios por nombre', function () {
    User::factory()->create(['name' => 'Pedro Buscable']);
    User::factory()->create(['name' => 'Ana Otro']);

    $this->visit('/admin/users')
        ->wait(2)
        ->type('input[placeholder*="Buscar"]', 'Pedro')
        ->wait(2)
        ->assertSee('Pedro Buscable')
        ->assertDontSee('Ana Otro')
        ->assertNoJavaScriptErrors();
});

test('admin puede filtrar usuarios por rol', function () {
    $profesor = User::factory()->create(['name' => 'Luis Profesor']);
    $profesor->assignRole('profesor');

    $alumno = User::factory()->create(['name' => 'Rosa Alumna']);
    $alumno->assignRole('alumno');

    $this->visit('/admin/users?role=profesor')
        ->wait(2)
        ->assertSee('Luis Profesor')
        ->assertDontSee('Rosa Alumna')
        ->assertNoJavaScriptErrors();
});

test('admin puede ver el detalle de un usuario', function () {
    $user = User::factory()->create(['name' => 'Juan Estudiante']);
    $user->assignRole('alumno');

    $this->visit('/admin/users')
        ->wait(2)
        ->click('text=Juan Estudiante')
        ->wait(2)
        ->assertPathIs("/admin/users/{$user->id}")
        ->assertSee('Juan Estudiante')
        ->assertNoJavaScriptErrors();
});

test('usuario sin permiso no puede acceder al listado de usuarios', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');

    $this->actingAs($alumno);

    // El usuario sin permiso recibe un error 403 o es redirigido
    $this->visit('/admin/users')
        ->wait(2)
        ->assertSee('403'); // Laravel muestra página de error 403
});

test('usuario CON permiso users.view SÍ puede ver listado (profesor)', function () {
    $profesor = User::factory()->create();
    $profesor->assignRole('profesor'); // profesor SÍ tiene users.view

    $this->actingAs($profesor);

    $this->visit('/admin/users')
        ->wait(2)
        ->assertPathIs('/admin/users')
        ->assertSee('Usuarios');
});

test('usuario sin permiso users.view NO puede ver listado (representante)', function () {
    $representante = User::factory()->create();
    $representante->assignRole('representante'); // representante NO tiene users.view

    $this->actingAs($representante);

    $this->visit('/admin/users')
        ->assertSee('403');
});
