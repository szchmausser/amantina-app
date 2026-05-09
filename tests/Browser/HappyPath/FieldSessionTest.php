<?php

namespace Tests\Browser\HappyPath;

use App\Models\AcademicYear;
use App\Models\FieldSession;
use App\Models\FieldSessionStatus;
use App\Models\User;
use Database\Seeders\FieldSessionStatusSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Pest\Browser\Browsable;

uses(DatabaseTruncation::class);
uses(Browsable::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->seed(FieldSessionStatusSeeder::class);

    $this->admin = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
    ]);
    $this->admin->assignRole('admin');

    $this->profesor = User::factory()->create(['name' => 'Prof. García']);
    $this->profesor->assignRole('profesor');

    $this->actingAs($this->admin);

    $this->academicYear = AcademicYear::factory()->create([
        'name' => '2024-2025',
        'is_active' => true,
    ]);

    $this->plannedStatus = FieldSessionStatus::where('name', 'planned')->first();
    $this->realizedStatus = FieldSessionStatus::where('name', 'realized')->first();
});

test('admin puede ver el listado de jornadas de campo', function () {
    $page = visit('/admin/field-sessions');

    $page->assertPathIs('/admin/field-sessions')
        ->assertSee('Jornadas')
        ->assertNoJavaScriptErrors();
});

test('admin puede ver jornadas existentes en el listado', function () {
    FieldSession::factory()->create([
        'name' => 'Siembra en Huerto Escolar',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $this->profesor->id,
        'status_id' => $this->plannedStatus->id,
    ]);

    $page = visit('/admin/field-sessions');

    $page->assertSee('Siembra en Huerto Escolar')
        ->assertNoJavaScriptErrors();
});

test('admin puede acceder al formulario de creación de jornada', function () {
    $page = visit('/admin/field-sessions/create');

    $page->assertPathIs('/admin/field-sessions/create')
        ->assertNoJavaScriptErrors();
});

test('admin puede ver el detalle de una jornada de campo', function () {
    $session = FieldSession::factory()->create([
        'name' => 'Cosecha de Tomates',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $this->profesor->id,
        'status_id' => $this->plannedStatus->id,
        'location_name' => 'Huerto escolar',
        'activity_name' => 'Cosecha',
    ]);

    $page = visit("/admin/field-sessions/{$session->id}");

    $page->assertSee('Cosecha de Tomates')
        ->assertNoJavaScriptErrors();
});

test('admin puede acceder al formulario de edición de jornada', function () {
    $session = FieldSession::factory()->create([
        'name' => 'Riego de Plantas',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $this->profesor->id,
        'status_id' => $this->plannedStatus->id,
    ]);

    $page = visit("/admin/field-sessions/{$session->id}/edit");

    // Verificar que la página carga (sin verificar contenido específico por ahora)
    $page->assertPathIs("/admin/field-sessions/{$session->id}/edit")
        ->assertNoJavaScriptErrors();
});

test('profesor puede ver sus propias jornadas', function () {
    $this->actingAs($this->profesor);

    FieldSession::factory()->create([
        'name' => 'Jornada del Profesor',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $this->profesor->id,
        'status_id' => $this->plannedStatus->id,
    ]);

    $page = visit('/admin/field-sessions');

    $page->assertSee('Jornada del Profesor')
        ->assertNoJavaScriptErrors();
});

test('admin puede filtrar jornadas por estado', function () {
    FieldSession::factory()->create([
        'name' => 'Jornada Planificada',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $this->profesor->id,
        'status_id' => $this->plannedStatus->id,
    ]);

    FieldSession::factory()->create([
        'name' => 'Jornada Realizada',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $this->profesor->id,
        'status_id' => $this->realizedStatus->id,
    ]);

    $page = visit('/admin/field-sessions?status='.$this->plannedStatus->id);

    $page->assertSee('Jornada Planificada')
        ->assertNoJavaScriptErrors();
});

// ============================================================================
// TESTS DE SEGURIDAD BASADOS EN PERMISOS
// ============================================================================

test('usuario sin permiso field_sessions.view NO puede ver listado (alumno)', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno'); // alumno NO tiene field_sessions.view

    $this->actingAs($alumno);

    $page = visit('/admin/field-sessions');

    $page->assertSee('403');
});

test('usuario sin permiso field_sessions.view NO puede ver detalle (alumno)', function () {
    $session = FieldSession::factory()->create([
        'name' => 'Jornada Privada',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $this->profesor->id,
        'status_id' => $this->plannedStatus->id,
    ]);

    $alumno = User::factory()->create();
    $alumno->assignRole('alumno'); // alumno NO tiene field_sessions.view

    $this->actingAs($alumno);

    $page = visit("/admin/field-sessions/{$session->id}");

    $page->assertSee('403');
});

test('usuario sin permiso field_sessions.create NO puede acceder a formulario de creación (alumno)', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno'); // alumno NO tiene field_sessions.create

    $this->actingAs($alumno);

    $page = visit('/admin/field-sessions/create');

    $page->assertSee('403');
});

test('usuario sin permiso field_sessions.view NO puede acceder a ninguna ruta (representante)', function () {
    $representante = User::factory()->create();
    $representante->assignRole('representante'); // representante NO tiene field_sessions.view

    $this->actingAs($representante);

    $page = visit('/admin/field-sessions');

    $page->assertSee('403');
});
