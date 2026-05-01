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
});

test('admin puede ver el listado de usuarios', function () {
    User::factory()->count(3)->create();

    $response = $this->actingAs($this->admin)->get(route('admin.users.index'));

    $response->assertStatus(200);
});

// ============================================================================
// TESTS DE AUTORIZACIÓN - POST (Crear usuarios)
// ============================================================================

test('alumno NO puede crear usuarios mediante POST directo', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');

    $response = $this->actingAs($alumno)->post(route('admin.users.store'), [
        'cedula' => 'V-99999999',
        'name' => 'Usuario Malicioso',
        'email' => 'malicioso@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'roles' => ['admin'], // Intentando asignarse admin!
    ]);

    $response->assertStatus(403);

    // Verificar que NO se creó
    $this->assertDatabaseMissing('users', [
        'email' => 'malicioso@test.com',
    ]);
});

test('profesor NO puede crear usuarios mediante POST directo', function () {
    $profesor = User::factory()->create();
    $profesor->assignRole('profesor');

    $response = $this->actingAs($profesor)->post(route('admin.users.store'), [
        'name' => 'Usuario Malicioso',
        'email' => 'malicioso@test.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'roles' => ['alumno'],
    ]);

    $response->assertStatus(403);

    // Verificar que NO se creó el usuario
    $this->assertDatabaseMissing('users', [
        'email' => 'malicioso@test.com',
    ]);
});

test('representante NO puede crear usuarios mediante POST directo', function () {
    $representante = User::factory()->create();
    $representante->assignRole('representante');

    $response = $this->actingAs($representante)->post(route('admin.users.store'), [
        'name' => 'Usuario Malicioso',
        'email' => 'malicioso2@test.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'roles' => ['alumno'],
    ]);

    $response->assertStatus(403);

    // Verificar que NO se creó el usuario
    $this->assertDatabaseMissing('users', [
        'email' => 'malicioso2@test.com',
    ]);
});

// ============================================================================
// TESTS DE AUTORIZACIÓN - PUT (Editar usuarios)
// ============================================================================

test('admin CON permiso users.edit SÍ puede editar otros usuarios mediante PUT', function () {
    $otroUsuario = User::factory()->create([
        'name' => 'Usuario Original',
        'cedula' => 'V-12345678',
        'phone' => '04121234567',
        'address' => 'Dirección Original',
    ]);
    $otroUsuario->assignRole('alumno');

    $response = $this->actingAs($this->admin)->put(route('admin.users.update', $otroUsuario), [
        'name' => 'Usuario Editado',
        'email' => $otroUsuario->email,
        'cedula' => $otroUsuario->cedula,
        'phone' => $otroUsuario->phone,
        'address' => $otroUsuario->address,
        'roles' => ['profesor'],
        'direct_permissions' => [],
    ]);

    $response->assertStatus(302); // Redirección exitosa

    // Verificar que SÍ se modificó
    $this->assertDatabaseHas('users', [
        'id' => $otroUsuario->id,
        'name' => 'Usuario Editado',
    ]);

    // Verificar que cambió el rol
    $otroUsuario->refresh();
    expect($otroUsuario->hasRole('profesor'))->toBeTrue();
});

test('profesor NO puede editar usuarios mediante PUT directo', function () {
    $profesor = User::factory()->create();
    $profesor->assignRole('profesor');

    $otroUsuario = User::factory()->create(['name' => 'Original']);

    $response = $this->actingAs($profesor)->put(route('admin.users.update', $otroUsuario), [
        'name' => 'Modificado por Profesor',
        'email' => $otroUsuario->email,
        'cedula' => $otroUsuario->cedula,
    ]);

    $response->assertStatus(403);

    // Verificar que NO se modificó
    $this->assertDatabaseHas('users', [
        'id' => $otroUsuario->id,
        'name' => 'Original',
    ]);
});

test('alumno NO puede editar usuarios mediante PUT directo', function () {
    $otroUsuario = User::factory()->create(['name' => 'Usuario Original']);
    $otroUsuario->assignRole('alumno');

    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');

    $response = $this->actingAs($alumno)->put(route('admin.users.update', $otroUsuario), [
        'name' => 'Usuario Editado Maliciosamente',
        'email' => $otroUsuario->email,
    ]);

    $response->assertStatus(403);

    // Verificar que NO se modificó
    $this->assertDatabaseHas('users', [
        'id' => $otroUsuario->id,
        'name' => 'Usuario Original',
    ]);
});

test('representante NO puede editar usuarios mediante PUT directo', function () {
    $otroUsuario = User::factory()->create(['name' => 'Usuario Original']);
    $otroUsuario->assignRole('alumno');

    $representante = User::factory()->create();
    $representante->assignRole('representante');

    $response = $this->actingAs($representante)->put(route('admin.users.update', $otroUsuario), [
        'name' => 'Usuario Editado Maliciosamente',
        'email' => $otroUsuario->email,
    ]);

    $response->assertStatus(403);

    // Verificar que NO se modificó
    $this->assertDatabaseHas('users', [
        'id' => $otroUsuario->id,
        'name' => 'Usuario Original',
    ]);
});

// ============================================================================
// TESTS DE AUTORIZACIÓN - DELETE (Eliminar usuarios)
// ============================================================================

test('admin CON permiso users.delete SÍ puede eliminar usuarios mediante DELETE', function () {
    $usuarioAEliminar = User::factory()->create(['name' => 'Usuario a Eliminar']);
    $usuarioAEliminar->assignRole('alumno');

    $response = $this->actingAs($this->admin)->delete(route('admin.users.destroy', $usuarioAEliminar));

    $response->assertStatus(302); // Redirección exitosa

    // Verificar que SÍ se eliminó (soft delete)
    $this->assertSoftDeleted('users', [
        'id' => $usuarioAEliminar->id,
    ]);
});

test('alumno NO puede eliminar usuarios mediante DELETE directo', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');

    $otroUsuario = User::factory()->create();

    $response = $this->actingAs($alumno)->delete(route('admin.users.destroy', $otroUsuario));

    $response->assertStatus(403);

    // Verificar que NO se eliminó
    $this->assertDatabaseHas('users', [
        'id' => $otroUsuario->id,
        'deleted_at' => null,
    ]);
});

test('profesor NO puede eliminar usuarios mediante DELETE directo', function () {
    $usuarioAEliminar = User::factory()->create(['name' => 'Usuario Protegido']);
    $usuarioAEliminar->assignRole('alumno');

    $profesor = User::factory()->create();
    $profesor->assignRole('profesor');

    $response = $this->actingAs($profesor)->delete(route('admin.users.destroy', $usuarioAEliminar));

    $response->assertStatus(403);

    // Verificar que NO se eliminó
    $this->assertDatabaseHas('users', [
        'id' => $usuarioAEliminar->id,
        'deleted_at' => null,
    ]);
});

test('representante NO puede eliminar usuarios mediante DELETE directo', function () {
    $usuarioAEliminar = User::factory()->create(['name' => 'Usuario Protegido']);
    $usuarioAEliminar->assignRole('alumno');

    $representante = User::factory()->create();
    $representante->assignRole('representante');

    $response = $this->actingAs($representante)->delete(route('admin.users.destroy', $usuarioAEliminar));

    $response->assertStatus(403);

    // Verificar que NO se eliminó
    $this->assertDatabaseHas('users', [
        'id' => $usuarioAEliminar->id,
        'deleted_at' => null,
    ]);
});

// ============================================================================
// TESTS DE PROTECCIÓN DE ROLES PROPIOS
// ============================================================================

test('alumno NO puede asignarse rol de admin mediante edición de perfil', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');

    $response = $this->actingAs($alumno)->put(route('admin.users.update', $alumno), [
        'name' => $alumno->name,
        'email' => $alumno->email,
        'cedula' => $alumno->cedula,
        'phone' => $alumno->phone ?? '04121234567',
        'address' => $alumno->address ?? 'Dirección de prueba',
        'roles' => ['admin'], // Intentando asignarse admin!
    ]);

    // Debe recibir 403 porque solo admin puede cambiar roles
    $response->assertStatus(403);

    // Verificar que NO tiene rol admin
    expect($alumno->fresh()->hasRole('admin'))->toBeFalse();
    expect($alumno->fresh()->hasRole('alumno'))->toBeTrue();
});

test('ni siquiera admin puede cambiar sus propios roles', function () {
    $response = $this->actingAs($this->admin)->put(route('admin.users.update', $this->admin), [
        'name' => $this->admin->name,
        'email' => $this->admin->email,
        'cedula' => $this->admin->cedula,
        'phone' => $this->admin->phone ?? '04121234567',
        'address' => $this->admin->address ?? 'Dirección de prueba',
        'roles' => ['profesor'], // Intentando cambiar su propio rol!
    ]);

    // Debe recibir 403 porque nadie puede cambiar sus propios roles
    $response->assertStatus(403);

    // Verificar que sigue siendo admin
    expect($this->admin->fresh()->hasRole('admin'))->toBeTrue();
    expect($this->admin->fresh()->hasRole('profesor'))->toBeFalse();
});

test('admin puede cambiar roles de OTROS usuarios', function () {
    $otroUsuario = User::factory()->create();
    $otroUsuario->assignRole('alumno');

    $response = $this->actingAs($this->admin)->put(route('admin.users.update', $otroUsuario), [
        'name' => $otroUsuario->name,
        'email' => $otroUsuario->email,
        'cedula' => $otroUsuario->cedula,
        'phone' => $otroUsuario->phone ?? '04121234567',
        'address' => $otroUsuario->address ?? 'Dirección de prueba',
        'roles' => ['profesor'],
    ]);

    $response->assertStatus(302); // Redirección exitosa

    // Verificar que SÍ cambió el rol
    expect($otroUsuario->fresh()->hasRole('profesor'))->toBeTrue();
    expect($otroUsuario->fresh()->hasRole('alumno'))->toBeFalse();
});
