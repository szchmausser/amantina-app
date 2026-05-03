<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Browsable;

uses(RefreshDatabase::class, Browsable::class);

/**
 * SECURITY TESTS: Admin Modules Access Control
 * 
 * Estos tests verifican que solo usuarios con rol admin pueden acceder a módulos administrativos:
 * - Admin: acceso completo a todos los módulos
 * - Profesor: acceso limitado a módulos operativos (enrollments, field-sessions, activity-categories, locations)
 * - Alumno: NO puede acceder a módulos admin (403)
 * - Representante: NO puede acceder a módulos admin (403)
 */

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

// ============================================================================
// TESTS: Alumno NO puede acceder a módulos administrativos
// ============================================================================

test('alumno no puede acceder a años escolares', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');
    
    $this->actingAs($alumno);
    
    $page = visit('/admin/academic-years');
    $page->wait(2);
    
    $page->assertSee('403');
});

test('alumno no puede acceder a lapsos académicos', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');
    
    $this->actingAs($alumno);
    
    $page = visit('/admin/school-terms');
    $page->wait(2);
    
    $page->assertSee('403');
});

test('alumno no puede acceder a grados', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');
    
    $this->actingAs($alumno);
    
    $page = visit('/admin/grades');
    $page->wait(2);
    
    $page->assertSee('403');
});

test('alumno no puede acceder a secciones', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');
    
    $this->actingAs($alumno);
    
    $page = visit('/admin/sections');
    $page->wait(2);
    
    $page->assertSee('403');
});

test('alumno no puede acceder a inscripciones', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');
    
    $this->actingAs($alumno);
    
    $page = visit('/admin/enrollments');
    $page->wait(2);
    
    $page->assertSee('403');
});

test('alumno no puede acceder a asignaciones de profesores', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');
    
    $this->actingAs($alumno);
    
    $page = visit('/admin/teacher-assignments');
    $page->wait(2);
    
    $page->assertSee('403');
});

test('alumno no puede acceder a jornadas de campo', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');
    
    $this->actingAs($alumno);
    
    $page = visit('/admin/field-sessions');
    $page->wait(2);
    
    $page->assertSee('403');
});

test('alumno no puede acceder a catálogos (categorías de actividad)', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');
    
    $this->actingAs($alumno);
    
    $page = visit('/admin/activity-categories');
    $page->wait(2);
    
    $page->assertSee('403');
});

test('alumno no puede acceder a catálogos (ubicaciones)', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');
    
    $this->actingAs($alumno);
    
    $page = visit('/admin/locations');
    $page->wait(2);
    
    $page->assertSee('403');
});

test('alumno no puede acceder a catálogos (condiciones de salud)', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');
    
    $this->actingAs($alumno);
    
    $page = visit('/admin/health-conditions');
    $page->wait(2);
    
    $page->assertSee('403');
});

// ============================================================================
// TESTS: Representante NO puede acceder a módulos administrativos
// ============================================================================

test('representante no puede acceder a años escolares', function () {
    $representante = User::factory()->create();
    $representante->assignRole('representante');
    
    $this->actingAs($representante);
    
    $page = visit('/admin/academic-years');
    $page->wait(2);
    
    $page->assertSee('403');
});

test('representante no puede acceder a lapsos académicos', function () {
    $representante = User::factory()->create();
    $representante->assignRole('representante');
    
    $this->actingAs($representante);
    
    $page = visit('/admin/school-terms');
    $page->wait(2);
    
    $page->assertSee('403');
});

test('representante no puede acceder a grados', function () {
    $representante = User::factory()->create();
    $representante->assignRole('representante');
    
    $this->actingAs($representante);
    
    $page = visit('/admin/grades');
    $page->wait(2);
    
    $page->assertSee('403');
});

test('representante no puede acceder a secciones', function () {
    $representante = User::factory()->create();
    $representante->assignRole('representante');
    
    $this->actingAs($representante);
    
    $page = visit('/admin/sections');
    $page->wait(2);
    
    $page->assertSee('403');
});

test('representante no puede acceder a inscripciones', function () {
    $representante = User::factory()->create();
    $representante->assignRole('representante');
    
    $this->actingAs($representante);
    
    $page = visit('/admin/enrollments');
    $page->wait(2);
    
    $page->assertSee('403');
});

test('representante no puede acceder a asignaciones de profesores', function () {
    $representante = User::factory()->create();
    $representante->assignRole('representante');
    
    $this->actingAs($representante);
    
    $page = visit('/admin/teacher-assignments');
    $page->wait(2);
    
    $page->assertSee('403');
});

test('representante no puede acceder a jornadas de campo', function () {
    $representante = User::factory()->create();
    $representante->assignRole('representante');
    
    $this->actingAs($representante);
    
    $page = visit('/admin/field-sessions');
    $page->wait(2);
    
    $page->assertSee('403');
});

test('representante no puede acceder a catálogos (categorías de actividad)', function () {
    $representante = User::factory()->create();
    $representante->assignRole('representante');
    
    $this->actingAs($representante);
    
    $page = visit('/admin/activity-categories');
    $page->wait(2);
    
    $page->assertSee('403');
});

test('representante no puede acceder a catálogos (ubicaciones)', function () {
    $representante = User::factory()->create();
    $representante->assignRole('representante');
    
    $this->actingAs($representante);
    
    $page = visit('/admin/locations');
    $page->wait(2);
    
    $page->assertSee('403');
});

test('representante no puede acceder a catálogos (condiciones de salud)', function () {
    $representante = User::factory()->create();
    $representante->assignRole('representante');
    
    $this->actingAs($representante);
    
    $page = visit('/admin/health-conditions');
    $page->wait(2);
    
    $page->assertSee('403');
});

// ============================================================================
// TESTS: Profesor NO puede acceder a módulos administrativos (excepto field-sessions)
// ============================================================================

test('profesor no puede acceder a años escolares', function () {
    $profesor = User::factory()->create();
    $profesor->assignRole('profesor');
    
    $this->actingAs($profesor);
    
    $page = visit('/admin/academic-years');
    $page->wait(2);
    
    $page->assertSee('403');
});

test('profesor no puede acceder a lapsos académicos', function () {
    $profesor = User::factory()->create();
    $profesor->assignRole('profesor');
    
    $this->actingAs($profesor);
    
    $page = visit('/admin/school-terms');
    $page->wait(2);
    
    $page->assertSee('403');
});

test('profesor no puede acceder a grados', function () {
    $profesor = User::factory()->create();
    $profesor->assignRole('profesor');
    
    $this->actingAs($profesor);
    
    $page = visit('/admin/grades');
    $page->wait(2);
    
    $page->assertSee('403');
});

test('profesor no puede acceder a secciones', function () {
    $profesor = User::factory()->create();
    $profesor->assignRole('profesor');
    
    $this->actingAs($profesor);
    
    $page = visit('/admin/sections');
    $page->wait(2);
    
    $page->assertSee('403');
});

test('profesor no puede acceder a asignaciones de profesores', function () {
    $profesor = User::factory()->create();
    $profesor->assignRole('profesor');
    
    $this->actingAs($profesor);
    
    $page = visit('/admin/teacher-assignments');
    $page->wait(2);
    
    $page->assertSee('403');
});

// Nota: Profesor SÍ puede acceder a estos módulos (tiene permisos):
// - /admin/enrollments (enrollments.view) - para ver sus alumnos
// - /admin/field-sessions (field_sessions.*) - para gestionar jornadas
// - /admin/activity-categories (activity_categories.*) - para gestionar categorías
// - /admin/locations (locations.*) - para gestionar ubicaciones
// Estos tests NO están incluidos porque son casos de acceso autorizado

test('profesor no puede acceder a catálogos (condiciones de salud)', function () {
    $profesor = User::factory()->create();
    $profesor->assignRole('profesor');
    
    $this->actingAs($profesor);
    
    $page = visit('/admin/health-conditions');
    $page->wait(2);
    
    $page->assertSee('403');
});
