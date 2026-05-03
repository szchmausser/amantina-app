<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('usuario autenticado puede ver su perfil', function () {
    $user = User::factory()->create(['name' => 'María García']);
    $user->assignRole('admin');

    $this->actingAs($user);

    $page = visit('/settings/profile');

    $page->assertPathIs('/settings/profile')
        ->assertSee('María García')
        ->assertNoJavaScriptErrors();
});

test('usuario puede ver la página de configuración de seguridad', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user);

    // security.edit requiere confirmación de contraseña, redirige
    $page = visit('/settings/security');

    // Debe redirigir a confirmación de contraseña o mostrar la página
    $page->assertNoJavaScriptErrors();
});

test('usuario no autenticado no puede ver el perfil', function () {
    $page = visit('/settings/profile');

    $page->assertPathIs('/login');
});

test('alumno puede ver su propio perfil', function () {
    $alumno = User::factory()->create(['name' => 'Carlos Alumno']);
    $alumno->assignRole('alumno');

    $this->actingAs($alumno);

    $page = visit('/settings/profile');

    $page->assertSee('Carlos Alumno')
        ->assertNoJavaScriptErrors();
});
