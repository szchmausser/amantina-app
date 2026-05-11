<?php

namespace Tests\Browser\HappyPath;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceActivity;
use App\Models\Enrollment;
use App\Models\FieldSession;
use App\Models\FieldSessionStatus;
use App\Models\Grade;
use App\Models\Section;
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

    $this->profesor = User::factory()->create(['name' => 'Prof. Martinez']);
    $this->profesor->assignRole('profesor');

    $this->actingAs($this->admin);

    $this->academicYear = AcademicYear::factory()->create([
        'name' => '2024-2025',
        'is_active' => true,
    ]);

    $this->grade = Grade::factory()->for($this->academicYear)->create(['name' => '1er Ano']);
    $this->section = Section::factory()->for($this->academicYear)->for($this->grade)->create(['name' => 'A']);

    $this->student = User::factory()->create(['name' => 'Luis Estudiante']);
    $this->student->assignRole('alumno');

    Enrollment::factory()
        ->for($this->academicYear)
        ->for($this->grade)
        ->for($this->section)
        ->for($this->student, 'student')
        ->create();

    $realizedStatus = FieldSessionStatus::where('name', 'realized')->first();

    $this->fieldSession = FieldSession::factory()->create([
        'name' => 'Jornada de Siembra',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $this->profesor->id,
        'status_id' => $realizedStatus->id,
        'start_datetime' => now()->subDay(),
        'end_datetime' => now()->subDay()->addHours(2),
        'base_hours' => 2.0,
    ]);
});

test('admin puede acceder a la pantalla de asistencia de una jornada', function () {
    $page = visit("/admin/field-sessions/{$this->fieldSession->id}/attendance");

    $page->assertPathIs("/admin/field-sessions/{$this->fieldSession->id}/attendance")
        ->assertSee('Jornada de Siembra')
        ->assertNoJavaScriptErrors();
});

test('pantalla de asistencia muestra los estudiantes inscritos', function () {
    $page = visit("/admin/field-sessions/{$this->fieldSession->id}/attendance");

    $page->waitForText('Luis Estudiante')
        ->assertNoJavaScriptErrors();
});

test('profesor puede acceder a la asistencia de sus propias jornadas', function () {
    $this->actingAs($this->profesor);

    $page = visit("/admin/field-sessions/{$this->fieldSession->id}/attendance");

    $page->assertPathIs("/admin/field-sessions/{$this->fieldSession->id}/attendance")
        ->assertSee('Jornada de Siembra')
        ->assertNoJavaScriptErrors();
});

test('alumno no puede acceder a la pantalla de asistencia', function () {
    $this->actingAs($this->student);

    $page = visit("/admin/field-sessions/{$this->fieldSession->id}/attendance");

    // El alumno recibe un error 403
    $page->assertSee('403');
});

test('representante NO puede acceder a la pantalla de asistencia', function () {
    $representante = User::factory()->create();
    $representante->assignRole('representante');

    $this->actingAs($representante);

    $page = visit("/admin/field-sessions/{$this->fieldSession->id}/attendance");

    $page->assertSee('403');
});

test('estudiante sin registrar muestra grado y seccion en columnas', function () {
    $page = visit("/admin/field-sessions/{$this->fieldSession->id}/attendance");

    $page->waitForText('Luis Estudiante')
        ->assertSee('1er Ano')
        ->assertSee('A')
        ->assertSee('Sin registrar')
        ->assertNoJavaScriptErrors();
});

test('busqueda por nombre filtra estudiantes', function () {
    $student2 = User::factory()->create(['name' => 'Maria Garcia']);
    $student2->assignRole('alumno');

    Enrollment::factory()
        ->for($this->academicYear)
        ->for($this->grade)
        ->for($this->section)
        ->for($student2, 'student')
        ->create();

    $page = visit("/admin/field-sessions/{$this->fieldSession->id}/attendance");

    $page->waitForText('Luis Estudiante')
        ->assertSee('Maria Garcia');

    $page->type('[data-test="search-input"]', 'Maria');

    $page->assertSee('Maria Garcia')
        ->assertDontSee('Luis Estudiante')
        ->assertNoJavaScriptErrors();
});

test('busqueda por cedula filtra estudiantes', function () {
    $student2 = User::factory()->create([
        'name' => 'Carlos Lopez',
        'cedula' => 'V-99887766',
    ]);
    $student2->assignRole('alumno');

    Enrollment::factory()
        ->for($this->academicYear)
        ->for($this->grade)
        ->for($this->section)
        ->for($student2, 'student')
        ->create();

    $page = visit("/admin/field-sessions/{$this->fieldSession->id}/attendance");

    $page->waitForText('Luis Estudiante')
        ->assertSee('Carlos Lopez');

    $page->type('[data-test="search-input"]', '99887766');

    $page->assertSee('Carlos Lopez')
        ->assertDontSee('Luis Estudiante')
        ->assertNoJavaScriptErrors();
});

test('boton volver navega a la jornada de campo', function () {
    $page = visit("/admin/field-sessions/{$this->fieldSession->id}/attendance");

    $page->waitForText('Registro de Asistencia');

    $page->click('[data-testid="back-button"]');

    $page->assertPathIs("/admin/field-sessions/{$this->fieldSession->id}")
        ->assertNoJavaScriptErrors();
});

test('filtro por grado filtra estudiantes', function () {
    $grade2 = Grade::factory()->for($this->academicYear)->create(['name' => '2do Ano']);
    $section2 = Section::factory()->for($this->academicYear)->for($grade2)->create(['name' => 'B']);

    $student2 = User::factory()->create(['name' => 'Maria Garcia']);
    $student2->assignRole('alumno');

    Enrollment::factory()
        ->for($this->academicYear)
        ->for($grade2)
        ->for($section2)
        ->for($student2, 'student')
        ->create();

    $page = visit("/admin/field-sessions/{$this->fieldSession->id}/attendance");

    $page->waitForText('Luis Estudiante')
        ->assertSee('Maria Garcia');

    // Select grade filter
    $page->click('[data-testid="grade-filter"]')
        ->waitForText('1er Ano')
        ->click('[data-testid="grade-filter"] [data-value="all"]');

    $page->assertNoJavaScriptErrors();
});

test('estudiante registrado muestra estado Registrado', function () {
    // Register the student first
    Attendance::create([
        'field_session_id' => $this->fieldSession->id,
        'user_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    $page = visit("/admin/field-sessions/{$this->fieldSession->id}/attendance");

    $page->waitForText('Luis Estudiante')
        ->assertSee('Registrado')
        ->assertNoJavaScriptErrors();
});

test('enlace del nombre del estudiante navega a su perfil', function () {
    $page = visit("/admin/field-sessions/{$this->fieldSession->id}/attendance");

    $page->waitForText('Luis Estudiante');

    $page->click("[data-testid=\"student-link-{$this->student->id}\"]");

    $page->assertPathIs("/admin/users/{$this->student->id}")
        ->assertNoJavaScriptErrors();
});

test('filtro por estado muestra solo estudiantes registrados', function () {
    // Register the student first
    Attendance::create([
        'field_session_id' => $this->fieldSession->id,
        'user_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    // Add a second student who is NOT registered
    $student2 = User::factory()->create(['name' => 'Maria Garcia']);
    $student2->assignRole('alumno');

    Enrollment::factory()
        ->for($this->academicYear)
        ->for($this->grade)
        ->for($this->section)
        ->for($student2, 'student')
        ->create();

    $page = visit("/admin/field-sessions/{$this->fieldSession->id}/attendance");

    $page->waitForText('Luis Estudiante')
        ->assertSee('Maria Garcia');

    // Filter by "Registrado" status
    $page->click('[data-testid="status-filter"]')
        ->waitForText('Registrado')
        ->click('[data-testid="status-filter"] [data-value="registered"]');

    $page->assertSee('Luis Estudiante')
        ->assertDontSee('Maria Garcia')
        ->assertNoJavaScriptErrors();
});

test('filtro por estado muestra solo estudiantes sin registrar', function () {
    // Register the student first
    Attendance::create([
        'field_session_id' => $this->fieldSession->id,
        'user_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    // Add a second student who is NOT registered
    $student2 = User::factory()->create(['name' => 'Maria Garcia']);
    $student2->assignRole('alumno');

    Enrollment::factory()
        ->for($this->academicYear)
        ->for($this->grade)
        ->for($this->section)
        ->for($student2, 'student')
        ->create();

    $page = visit("/admin/field-sessions/{$this->fieldSession->id}/attendance");

    $page->waitForText('Luis Estudiante')
        ->assertSee('Maria Garcia');

    // Filter by "Sin registrar" status
    $page->click('[data-testid="status-filter"]')
        ->waitForText('Sin registrar')
        ->click('[data-testid="status-filter"] [data-value="unregistered"]');

    $page->assertSee('Maria Garcia')
        ->assertDontSee('Luis Estudiante')
        ->assertNoJavaScriptErrors();
});

test('admin puede desregistrar estudiante sin actividades', function () {
    // Register the student
    Attendance::create([
        'field_session_id' => $this->fieldSession->id,
        'user_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    $page = visit("/admin/field-sessions/{$this->fieldSession->id}/attendance");

    $page->waitForText('Luis Estudiante')
        ->assertSee('Registrado');

    // Click unregister button
    $page->click("[data-testid=\"unregister-button-{$this->student->id}\"]")
        ->waitForText('¿Quitar estudiante de la jornada?');

    // Confirm unregister
    $page->click('[data-testid="confirm-unregister-button"]');

    // Student should now show as unregistered
    $page->waitForText('Sin registrar')
        ->assertSee('Sin registrar')
        ->assertNoJavaScriptErrors();
});

test('no se puede desregistrar estudiante con actividades registradas', function () {
    // Register the student with an activity
    $attendance = Attendance::create([
        'field_session_id' => $this->fieldSession->id,
        'user_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    // Create an activity for this attendance
    AttendanceActivity::factory()->for($attendance)->create();

    $page = visit("/admin/field-sessions/{$this->fieldSession->id}/attendance");

    $page->waitForText('Luis Estudiante')
        ->assertSee('Registrado');

    // The unregister button should be disabled
    $page->assertAttribute(
        "[data-testid=\"unregister-button-{$this->student->id}\"]",
        'disabled',
        'true'
    );

    // Hover over the button to see tooltip
    $page->hover("[data-testid=\"unregister-button-{$this->student->id}\"]")
        ->waitForText('Debe eliminar las actividades primero');

    // Student should still be registered
    $page->assertSee('Registrado')
        ->assertNoJavaScriptErrors();
});
