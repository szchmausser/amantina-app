<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Pest\Browser\Browsable;

uses(DatabaseTruncation::class);
uses(Browsable::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

// ============================================================================
// Tests
// ============================================================================

test('admin puede iniciar sesión y llegar al dashboard', function () {
    $admin = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
    ]);
    $admin->assignRole('admin');

    $this->visit('/login')
        ->assertSee('Iniciar Sesión')
        ->type('[name="email"]', 'admin@test.com')
        ->type('[name="password"]', 'password')
        ->select('[name="context"]', 'admin')
        ->click('[data-testid="login-button"]')

        ->assertPathIs('/dashboard')
        ->assertSee('Panel de Administración')
        ->assertNoJavaScriptErrors();
});

test('credenciales incorrectas muestran error de validación', function () {
    $this->visit('/login')
        ->type('[name="email"]', 'noexiste@test.com')
        ->type('[name="password"]', 'wrongpassword')
        ->click('[data-testid="login-button"]')

        ->assertPathIs('/login')
        ->assertSee('do not match');
});

test('usuario sin verificar email puede iniciar sesión', function () {
    $user = User::factory()->unverified()->create([
        'email' => 'unverified@test.com',
        'password' => bcrypt('password'),
    ]);
    $user->assignRole('alumno');

    // Laravel Fortify permite login sin verificación por defecto
    // El middleware EnsureEmailIsVerified debe estar en las rutas protegidas
    $this->visit('/login')
        ->type('[name="email"]', 'unverified@test.com')
        ->type('[name="password"]', 'password')
        ->select('[name="context"]', 'alumno')
        ->click('[data-testid="login-button"]')

        ->assertPathIs('/dashboard');
})->skip('Requiere configurar middleware EnsureEmailIsVerified en rutas protegidas');

test('usuario autenticado es redirigido al dashboard desde login', function () {
    $admin = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
    ]);
    $admin->assignRole('admin');

    // Login real por UI (como haría un usuario)
    $this->visit('/login')
        ->type('[name="email"]', 'admin@test.com')
        ->type('[name="password"]', 'password')
        ->select('[name="context"]', 'admin')
        ->click('[data-testid="login-button"]');

    // Intentar visitar login estando autenticado
    $this->visit('/login')

        ->assertPathIs('/dashboard');
});

test('usuario puede cerrar sesión', function () {
    $admin = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
    ]);
    $admin->assignRole('admin');

    // Login real por UI
    $this->visit('/login')
        ->type('[name="email"]', 'admin@test.com')
        ->type('[name="password"]', 'password')
        ->select('[name="context"]', 'admin')
        ->click('[data-testid="login-button"]');

    // Logout real por UI: abrir menú de usuario en sidebar y click en logout
    $this->visit('/dashboard')
        ->click('[data-test="sidebar-menu-button"]')

        ->click('[data-test="logout-button"]')

        ->assertPathIs('/');

    // Verificar que ya no tiene acceso al dashboard (redirige a login)
    $this->visit('/dashboard')

        ->assertPathIs('/login');
});
