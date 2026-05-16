<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Pest\Browser\Browsable;

uses(DatabaseTruncation::class);
uses(Browsable::class);

/**
 * SECURITY TESTS: Admin Modules Access Control
 *
 * Verifica que usuarios sin permisos adecuados no pueden acceder a módulos administrativos.
 * Usa data providers para consolidar tests repetitivos y mantener cobertura sin duplicación.
 *
 * Cobertura:
 * - Alumno: NO puede acceder a ningún módulo admin
 * - Representante: NO puede acceder a ningún módulo admin
 * - Profesor: acceso a módulos operativos con permiso de vista (academic-years, school-terms, grades, sections,
 *   field-sessions, activity-categories, locations, users) pero NO a módulos administrativos
 *   ni de gestión sensible (enrollments, teacher-assignments, health-conditions, definitions).
 *   student-health y external-hours no tienen rutas GET propias (embebidas en /admin/users/{user}).
 */
beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

// ============================================================================
// Data providers
// ============================================================================

/**
 * Módulos a los que alumno y representante NO pueden acceder.
 * [role, route, expectedPageTitle]
 */
dataset('forbidden_for_student_and_representative', [
    ['alumno', '/admin/academic-years', 'Años Escolares'],
    ['alumno', '/admin/school-terms', 'Lapsos Académicos'],
    ['alumno', '/admin/grades', 'Grados'],
    ['alumno', '/admin/sections', 'Secciones'],
    ['alumno', '/admin/enrollments', 'Inscripciones'],
    ['alumno', '/admin/teacher-assignments', 'Asignaciones Docentes'],
    ['alumno', '/admin/field-sessions', 'Jornadas de Campo'],
    ['alumno', '/admin/activity-categories', 'Categorías de Actividades'],
    ['alumno', '/admin/locations', 'Ubicaciones'],
    ['alumno', '/admin/health-conditions', 'Condiciones de Salud'],
    ['alumno', '/admin/grade-definitions', 'Definiciones de Grados'],
    ['alumno', '/admin/section-definitions', 'Definiciones de Secciones'],

    ['representante', '/admin/academic-years', 'Años Escolares'],
    ['representante', '/admin/school-terms', 'Lapsos Académicos'],
    ['representante', '/admin/grades', 'Grados'],
    ['representante', '/admin/sections', 'Secciones'],
    ['representante', '/admin/enrollments', 'Inscripciones'],
    ['representante', '/admin/teacher-assignments', 'Asignaciones Docentes'],
    ['representante', '/admin/field-sessions', 'Jornadas de Campo'],
    ['representante', '/admin/activity-categories', 'Categorías de Actividades'],
    ['representante', '/admin/locations', 'Ubicaciones'],
    ['representante', '/admin/health-conditions', 'Condiciones de Salud'],
    ['representante', '/admin/grade-definitions', 'Definiciones de Grados'],
    ['representante', '/admin/section-definitions', 'Definiciones de Secciones'],
]);

/**
 * Módulos a los que profesor SÍ puede acceder (tiene permiso de vista).
 */
dataset('allowed_for_profesor', [
    ['/admin/academic-years', 'Años Escolares'],
    ['/admin/school-terms', 'Lapsos Académicos'],
    ['/admin/grades', 'Grados'],
    ['/admin/sections', 'Secciones'],
]);

/**
 * Módulos a los que profesor NO puede acceder (solo admin).
 * Profesor SÍ puede acceder a: field-sessions, activity-categories, locations, users (vista).
 *
 * NOTA: student-health y external-hours están excluidas porque NO tienen rutas GET propias.
 * Están embebidas como secciones/modal dentro de /admin/users/{user}.
 * Visitar esas URLs directamente devuelve 404 (no existe ruta GET) — no 403.
 * Un profesor con acceso de vista a /admin/users/{id} ve esos datos allí.
 */
dataset('forbidden_for_profesor', [
    ['/admin/teacher-assignments', 'Asignaciones Docentes'],
    ['/admin/health-conditions', 'Condiciones de Salud'],
    ['/admin/grade-definitions', 'Definiciones de Grados'],
    ['/admin/section-definitions', 'Definiciones de Secciones'],
    ['/admin/enrollments', 'Inscripciones'],
]);


// ============================================================================
// Tests consolidados
// ============================================================================

test('rol sin permisos no puede acceder a módulo administrativo', function ($role, $route, $titleText) {
    $user = User::factory()->create();
    $user->assignRole($role);

    $this->actingAs($user);

    $page = visit($route);
    $page->wait(2);

    $page->assertSee('403');
    $page->assertDontSee($titleText);
})->with('forbidden_for_student_and_representative');

test('profesor no puede acceder a módulo solo-admin', function ($route, $titleText) {
    $profesor = User::factory()->create();
    $profesor->assignRole('profesor');

    $this->actingAs($profesor);

    $page = visit($route);
    $page->wait(2);

    $page->assertSee('403');
    $page->assertDontSee($titleText);
})->with('forbidden_for_profesor');

test('profesor puede acceder a módulo con permiso de vista', function ($route, $titleText) {
    $profesor = User::factory()->create();
    $profesor->assignRole('profesor');

    $this->actingAs($profesor);

    $page = visit($route);
    $page->wait(2);

    $page->assertDontSee('403');
    $page->assertSee($titleText);
})->with('allowed_for_profesor');
