<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Browsable;

uses(RefreshDatabase::class, Browsable::class);

/**
 * SECURITY TESTS: User Management Access Control
 * 
 * Estos tests verifican que el control de acceso al módulo de usuarios funciona correctamente:
 * - Admin: acceso completo
 * - Profesor: puede ver listado (tiene permiso users.view)
 * - Alumno: NO puede acceder (403)
 * - Representante: NO puede acceder (403)
 */

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

// ============================================================================
// TESTS: Admin tiene acceso completo
// ============================================================================

test('admin puede acceder al listado de usuarios', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    
    $this->actingAs($admin);
    
    $page = visit('/admin/users');
    $page->wait(2);
    
    $page->assertPathIs('/admin/users');
    $page->assertSee('Usuarios');
});

test('admin puede acceder al formulario de creación', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    
    $this->actingAs($admin);
    
    $page = visit('/admin/users/create');
    $page->wait(2);
    
    $page->assertPathIs('/admin/users/create');
    $page->assertSee('Nuevo Usuario');
});

test('admin puede acceder al detalle de un usuario', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    
    $user = User::factory()->create(['name' => 'Juan Test']);
    $user->assignRole('alumno');
    
    $this->actingAs($admin);
    
    $page = visit("/admin/users/{$user->id}");
    $page->wait(2);
    
    $page->assertPathIs("/admin/users/{$user->id}");
    $page->assertSee('Juan Test');
});

test('admin puede acceder al formulario de edición', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    
    $user = User::factory()->create();
    $user->assignRole('alumno');
    
    $this->actingAs($admin);
    
    $page = visit("/admin/users/{$user->id}/edit");
    $page->wait(2);
    
    $page->assertPathIs("/admin/users/{$user->id}/edit");
    $page->assertSee('Editar Usuario');
});

// ============================================================================
// TESTS: Profesor puede ver listado (tiene permiso users.view)
// ============================================================================

test('profesor puede acceder al listado de usuarios', function () {
    $profesor = User::factory()->create();
    $profesor->assignRole('profesor');
    
    $this->actingAs($profesor);
    
    $page = visit('/admin/users');
    $page->wait(2);
    
    $page->assertPathIs('/admin/users');
    $page->assertSee('Usuarios');
});

test('profesor puede ver detalle de un usuario', function () {
    $profesor = User::factory()->create();
    $profesor->assignRole('profesor');
    
    $user = User::factory()->create(['name' => 'María Test']);
    $user->assignRole('alumno');
    
    $this->actingAs($profesor);
    
    $page = visit("/admin/users/{$user->id}");
    $page->wait(2);
    
    $page->assertPathIs("/admin/users/{$user->id}");
    $page->assertSee('María Test');
});

// ============================================================================
// TESTS: Alumno NO puede acceder (403)
// ============================================================================

test('alumno no puede acceder al listado de usuarios', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');
    
    $this->actingAs($alumno);
    
    $page = visit('/admin/users');
    $page->wait(2);
    
    $page->assertSee('403');
});

test('alumno no puede acceder al formulario de creación', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');
    
    $this->actingAs($alumno);
    
    $page = visit('/admin/users/create');
    $page->wait(2);
    
    $page->assertSee('403');
});

test('alumno no puede acceder al detalle de un usuario', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');
    
    $user = User::factory()->create();
    $user->assignRole('profesor');
    
    $this->actingAs($alumno);
    
    $page = visit("/admin/users/{$user->id}");
    $page->wait(2);
    
    $page->assertSee('403');
});

test('alumno no puede acceder al formulario de edición', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');
    
    $user = User::factory()->create();
    $user->assignRole('profesor');
    
    $this->actingAs($alumno);
    
    $page = visit("/admin/users/{$user->id}/edit");
    $page->wait(2);
    
    $page->assertSee('403');
});

// ============================================================================
// TESTS: Representante NO puede acceder (403)
// ============================================================================

test('representante no puede acceder al listado de usuarios', function () {
    $representante = User::factory()->create();
    $representante->assignRole('representante');
    
    $this->actingAs($representante);
    
    $page = visit('/admin/users');
    $page->wait(2);
    
    $page->assertSee('403');
});

test('representante no puede acceder al formulario de creación', function () {
    $representante = User::factory()->create();
    $representante->assignRole('representante');
    
    $this->actingAs($representante);
    
    $page = visit('/admin/users/create');
    $page->wait(2);
    
    $page->assertSee('403');
});

test('representante no puede acceder al detalle de un usuario', function () {
    $representante = User::factory()->create();
    $representante->assignRole('representante');
    
    $user = User::factory()->create();
    $user->assignRole('alumno');
    
    $this->actingAs($representante);
    
    $page = visit("/admin/users/{$user->id}");
    $page->wait(2);
    
    $page->assertSee('403');
});

test('representante no puede acceder al formulario de edición', function () {
    $representante = User::factory()->create();
    $representante->assignRole('representante');
    
    $user = User::factory()->create();
    $user->assignRole('alumno');
    
    $this->actingAs($representante);
    
    $page = visit("/admin/users/{$user->id}/edit");
    $page->wait(2);
    
    $page->assertSee('403');
});
