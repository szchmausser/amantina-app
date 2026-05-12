<?php

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Browsable;

uses(RefreshDatabase::class, Browsable::class);

/**
 * SECURITY TESTS: Admin Modules Access Control
 *
 * Verifica que usuarios sin permisos adecuados no pueden acceder a módulos administrativos.
 * Usa data providers para consolidar tests repetitivos y mantener cobertura sin duplicación.
 *
 * Cobertura:
 * - Alumno: NO puede acceder a ningún módulo admin
 * - Representante: NO puede acceder a ningún módulo admin
 * - Profesor: acceso limitado (solo módulos operativos con permiso explícito)
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
    ['alumno', '/admin/student-health', 'Salud de Estudiantes'],
    ['alumno', '/admin/external-hours', 'Horas Externas'],
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
    ['representante', '/admin/student-health', 'Salud de Estudiantes'],
    ['representante', '/admin/external-hours', 'Horas Externas'],
    ['representante', '/admin/grade-definitions', 'Definiciones de Grados'],
    ['representante', '/admin/section-definitions', 'Definiciones de Secciones'],
]);

/**
 * Módulos a los que profesor NO puede acceder (solo admin).
 * Profesor SÍ puede acceder a: field-sessions, activity-categories, locations, enrollments.
 */
dataset('forbidden_for_profesor', [
    ['/admin/academic-years', 'Años Escolares'],
    ['/admin/school-terms', 'Lapsos Académicos'],
    ['/admin/grades', 'Grados'],
    ['/admin/sections', 'Secciones'],
    ['/admin/teacher-assignments', 'Asignaciones Docentes'],
    ['/admin/health-conditions', 'Condiciones de Salud'],
    ['/admin/student-health', 'Salud de Estudiantes'],
    ['/admin/external-hours', 'Horas Externas'],
    ['/admin/grade-definitions', 'Definiciones de Grados'],
    ['/admin/section-definitions', 'Definiciones de Secciones'],
    ['/admin/enrollments', 'Inscripciones'],
]);

// ============================================================================
// Tests consolidados
// ============================================================================

test('rol sin permisos no puede acceder a módulo administrativo')
    ->with('forbidden_for_student_and_representative')
    ->assert(function ($role, $route, $titleText) {
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user);

        $page = visit($route);
        $page->wait(2);

        $page->assertSee('403');
        $page->assertDontSee($titleText);
    });

test('profesor no puede acceder a módulo solo-admin')
    ->with('forbidden_for_profesor')
    ->assert(function ($route, $titleText) {
        $profesor = User::factory()->create();
        $profesor->assignRole('profesor');

        $this->actingAs($profesor);

        $page = visit($route);
        $page->wait(2);

        $page->assertSee('403');
        $page->assertDontSee($titleText);
    });
