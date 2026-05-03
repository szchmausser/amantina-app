<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

test('admin puede iniciar sesión (quick)', function () {
    $admin = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
    ]);
    $admin->assignRole('admin');

    $this->visit('/login')
        ->assertSee('Iniciar sesión')
        ->type('#email', 'admin@test.com')
        ->type('#password', 'password')
        ->select('#context', 'admin')
        ->click('[data-test="login-button"]')
        ->wait(3)
        ->assertPathIs('/dashboard')
        ->assertNoJavaScriptErrors();
});

test('credenciales incorrectas (quick)', function () {
    $this->visit('/login')
        ->type('#email', 'noexiste@test.com')
        ->type('#password', 'wrongpassword')
        ->click('[data-test="login-button"]')
        ->wait(2)
        ->assertPathIs('/login');
});
