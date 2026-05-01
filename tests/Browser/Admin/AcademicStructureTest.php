<?php

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\SchoolTerm;
use App\Models\Section;
use App\Models\TermType;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->admin = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
    ]);
    $this->admin->assignRole('admin');

    $this->actingAs($this->admin);

    $this->academicYear = AcademicYear::factory()->create([
        'name' => '2024-2025',
        'is_active' => true,
    ]);
});

// ─── GRADOS ───────────────────────────────────────────────────────────────────

test('admin puede ver el listado de grados', function () {
    Grade::factory()->for($this->academicYear)->count(3)->create();

    $this->visit('/admin/grades')
        ->wait(2)
        ->assertPathIs('/admin/grades')
        ->assertSee('Grados Académicos')
        ->assertNoJavaScriptErrors();
});

test('admin puede ver grados del año activo', function () {
    Grade::factory()->for($this->academicYear)->create(['name' => '1er Año']);

    $this->visit('/admin/grades')
        ->wait(2)
        ->assertSee('1er Año')
        ->assertNoJavaScriptErrors();
});

// ─── SECCIONES ────────────────────────────────────────────────────────────────

test('admin puede ver el listado de secciones', function () {
    $grade = Grade::factory()->for($this->academicYear)->create();
    Section::factory()->for($this->academicYear)->for($grade)->count(2)->create();

    $this->visit('/admin/sections')
        ->wait(2)
        ->assertPathIs('/admin/sections')
        ->assertSee('Secciones')
        ->assertNoJavaScriptErrors();
});

test('admin puede ver el detalle de una sección', function () {
    $grade = Grade::factory()->for($this->academicYear)->create(['name' => '1er Año']);
    $section = Section::factory()->for($this->academicYear)->for($grade)->create(['name' => 'A']);

    $this->visit("/admin/sections/{$section->id}")
        ->wait(2)
        ->assertSee('A')
        ->assertSee('1er Año')
        ->assertNoJavaScriptErrors();
});

// ─── LAPSOS ESCOLARES ─────────────────────────────────────────────────────────

test('admin puede ver el listado de lapsos escolares', function () {
    $this->visit('/admin/school-terms')
        ->wait(2)
        ->assertPathIs('/admin/school-terms')
        ->assertSee('Lapsos Académicos')
        ->assertNoJavaScriptErrors();
});

test('admin puede ver lapsos del año activo', function () {
    $termType = TermType::firstOrCreate(['name' => 'Lapso 1'], ['order' => 1]);

    SchoolTerm::factory()->create([
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $termType->id,
        'start_date' => '2024-09-01',
        'end_date' => '2024-11-30',
    ]);

    $this->visit('/admin/school-terms')
        ->wait(2)
        ->assertPathIs('/admin/school-terms')
        ->assertNoJavaScriptErrors();

    // Verificar en base de datos que el lapso existe
    $this->assertDatabaseHas('school_terms', [
        'academic_year_id' => $this->academicYear->id,
        'term_type_id' => $termType->id,
    ]);
});

// ─── INFORMACIÓN ACADÉMICA ────────────────────────────────────────────────────

test('admin puede ver la vista general de información académica', function () {
    $this->visit('/admin/academic-info')
        ->wait(2)
        ->assertPathIs('/admin/academic-info')
        ->assertNoJavaScriptErrors();
});

// ─── CONTROL DE ACCESO ────────────────────────────────────────────────────────

test('usuario sin permiso no puede acceder a la gestión de estructura académica', function () {
    $alumno = User::factory()->create();
    $alumno->assignRole('alumno');

    $this->actingAs($alumno);

    // No puede acceder a grados
    $this->visit('/admin/grades')
        ->wait(2)
        ->assertSee('403');

    // No puede acceder a secciones
    $this->visit('/admin/sections')
        ->wait(2)
        ->assertSee('403');

    // No puede acceder a lapsos
    $this->visit('/admin/school-terms')
        ->wait(2)
        ->assertSee('403');
});
