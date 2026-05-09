<?php

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\GradeDefinition;
use App\Models\SchoolTerm;
use App\Models\Section;
use App\Models\SectionDefinition;
use App\Models\TeacherAssignment;
use App\Models\TermType;
use App\Models\User;
use Database\Seeders\FieldSessionStatusSeeder;
use Database\Seeders\GradeDefinitionSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SectionDefinitionSeeder;
use Database\Seeders\TermTypeSeeder;

/**
 * Happy Path: Admin Full Flow
 *
 * Este test modela el flujo completo de un administrador configurando
 * el sistema desde cero. Cada paso es prerequisito del siguiente:
 *
 * 1. Login como admin
 * 2. Crear Año Escolar
 * 3. Crear Lapsos Académicos
 * 4. Crear Grados
 * 5. Crear Secciones
 * 6. Crear Usuarios (profesor + alumno)
 * 7. Inscribir alumno en sección
 * 8. Asignar profesor a sección
 *
 * Al finalizar, el sistema está listo para que un profesor cree jornadas
 * y registre asistencia (ver TeacherJourneyTest).
 */
beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    $this->seed(TermTypeSeeder::class);
    $this->seed(FieldSessionStatusSeeder::class);
    $this->seed(GradeDefinitionSeeder::class);
    $this->seed(SectionDefinitionSeeder::class);

    $this->admin = User::factory()->create([
        'email' => 'admin@happytest.com',
        'password' => bcrypt('password'),
    ]);
    $this->admin->assignRole('admin');

    // NOTE: actingAs() is NOT called here intentionally.
    // The login test needs to start without an active session so Fortify's guest
    // middleware does not redirect /login -> /dashboard before #email renders.
    // Each test that requires auth calls actingAs() individually.
});

// ============================================================================
// PASO 1: Login como admin
// ============================================================================

test('admin puede iniciar sesión y llegar al dashboard', function () {
    // Visit login page directly (browser tests start with clean session)
    $page = visit('/login');
    $page->wait(1);

    // Fill login form
    $page->type('#email', 'admin@happytest.com');
    $page->wait(0.3);
    $page->type('#password', 'password');
    $page->wait(0.3);
    $page->select('#context', 'admin');
    $page->wait(0.3);

    // Submit
    $page->click('[data-test="login-button"]');
    $page->wait(3);

    // Verify redirect to dashboard
    $page->assertPathIs('/dashboard');
});

// ============================================================================
// PASO 2: Crear Año Escolar
// ============================================================================

test('admin puede crear un año escolar completo', function () {
    $this->actingAs($this->admin);

    $testAcademicYearName = 'Año-Test-'.uniqid();

    $page = visit('/admin/academic-years/create');
    $page->wait(2);
    $page->assertSee('Nuevo Año Escolar');
    // Fill form
    $page->type('#name', $testAcademicYearName);
    $page->wait(0.5);
    $page->type('#start_date', '2025-09-15');
    $page->wait(0.5);
    $page->type('#end_date', '2026-07-15');
    $page->wait(0.5);
    $page->clear('#required_hours');
    $page->type('#required_hours', '600');
    $page->wait(0.5);
    // Activate the year
    $page->click('button#is_active');
    $page->wait(1);
    // Submit
    $page->click('button[type="submit"]');
    $page->wait(5);
    $page->assertPathIs('/admin/academic-years');
    $page->assertSee('Años Escolares');
    $page->assertSee($testAcademicYearName);
});

// ============================================================================
// PASO 3: Crear Lapsos Académicos
// ============================================================================

test('admin puede crear lapsos académicos para el año escolar', function () {
    $this->actingAs($this->admin);

    $academicYearName = 'Lapsos-Test-'.uniqid();

    // Setup: create academic year via factory (already tested in step 2)
    $academicYear = AcademicYear::factory()->create([
        'name' => $academicYearName,
        'start_date' => '2025-09-01',
        'end_date' => '2026-07-15',
        'is_active' => true,
        'required_hours' => 600,
    ]);

    // ===== CREATE LAPSO 1 =====
    $page = visit('/admin/school-terms/create');
    $page->wait(2);
    $page->assertSee('Nuevo Lapso Académico');

    // Select academic year
    $page->click('[data-test="academic-year-select-trigger"]');
    $page->wait(0.5);
    $page->click('[role="option"]:has-text("'.$academicYearName.'")');
    $page->wait(0.5);

    // Select term type (Lapso 1)
    $page->click('[data-test="term-type-select-trigger"]');
    $page->wait(0.5);
    $page->click('[role="option"]:has-text("Lapso 1")');
    $page->wait(0.5);

    // Fill dates
    $page->type('[data-test="start-date-input"]', '2025-09-01');
    $page->wait(0.3);
    $page->type('[data-test="end-date-input"]', '2025-12-15');
    $page->wait(0.3);

    // Submit
    $page->click('[data-test="submit-button"]');
    $page->wait(3);

    // ===== CREATE LAPSO 2 =====
    $page = visit('/admin/school-terms/create');
    $page->wait(2);

    // Select academic year
    $page->click('[data-test="academic-year-select-trigger"]');
    $page->wait(0.5);
    $page->click('[role="option"]:has-text("'.$academicYearName.'")');
    $page->wait(0.5);

    // Select term type (Lapso 2)
    $page->click('[data-test="term-type-select-trigger"]');
    $page->wait(0.5);
    $page->click('[role="option"]:has-text("Lapso 2")');
    $page->wait(0.5);

    // Fill dates (no overlap with Lapso 1)
    $page->type('[data-test="start-date-input"]', '2026-01-15');
    $page->wait(0.3);
    $page->type('[data-test="end-date-input"]', '2026-04-15');
    $page->wait(0.3);

    // Submit
    $page->click('[data-test="submit-button"]');
    $page->wait(3);

    // ===== CREATE LAPSO 3 =====
    $page = visit('/admin/school-terms/create');
    $page->wait(2);

    // Select academic year
    $page->click('[data-test="academic-year-select-trigger"]');
    $page->wait(0.5);
    $page->click('[role="option"]:has-text("'.$academicYearName.'")');
    $page->wait(0.5);

    // Select term type (Lapso 3)
    $page->click('[data-test="term-type-select-trigger"]');
    $page->wait(0.5);
    $page->click('[role="option"]:has-text("Lapso 3")');
    $page->wait(0.5);

    // Fill dates (no overlap with Lapso 2)
    $page->type('[data-test="start-date-input"]', '2026-04-20');
    $page->wait(0.3);
    $page->type('[data-test="end-date-input"]', '2026-07-15');
    $page->wait(0.3);

    // Submit
    $page->click('[data-test="submit-button"]');
    $page->wait(3);

    // Verify all 3 lapsos appear in the listing
    $page = visit('/admin/school-terms?academic_year_id='.$academicYear->id);
    $page->wait(2);
    $page->assertPathIs('/admin/school-terms');
    $page->assertSee('Lapso 1');
    $page->assertSee('Lapso 2');
    $page->assertSee('Lapso 3');
});

// ============================================================================
// PASO 4: Crear Grados
// ============================================================================

test('admin puede crear grados para el año escolar', function () {
    $this->actingAs($this->admin);

    $academicYearName = 'Grados-Test-'.uniqid();

    $academicYear = AcademicYear::factory()->create([
        'name' => $academicYearName,
        'is_active' => true,
    ]);

    // ===== CREATE 1ER AÑO =====
    $page = visit('/admin/grades/create');
    $page->wait(2);
    $page->assertSee('Nuevo Grado Académico');

    // Select academic year
    $page->click('[data-test="academic-year-select-trigger"]');
    $page->wait(1);
    $page->assertSee($academicYearName);
    $page->click('[role="option"]:has-text("'.$academicYearName.'")');
    $page->wait(1);

    // Select grade definition
    $page->click('[data-test="grade-definition-select-trigger"]');
    $page->wait(0.5);
    $page->click('[role="option"]:has-text("1er Año")');
    $page->wait(0.5);

    // Fill order
    $page->clear('[data-test="grade-order-input"]');
    $page->type('[data-test="grade-order-input"]', '1');
    $page->wait(0.3);

    // Submit
    $page->click('[data-test="submit-button"]');
    $page->wait(3);

    // ===== CREATE 2DO AÑO =====
    $page = visit('/admin/grades/create');
    $page->wait(2);

    $page->click('[data-test="academic-year-select-trigger"]');
    $page->wait(1);
    $page->assertSee($academicYearName);
    $page->click('[role="option"]:has-text("'.$academicYearName.'")');
    $page->wait(1);

    $page->click('[data-test="grade-definition-select-trigger"]');
    $page->wait(0.5);
    $page->click('[role="option"]:has-text("2do Año")');
    $page->wait(0.5);

    $page->clear('[data-test="grade-order-input"]');
    $page->type('[data-test="grade-order-input"]', '2');
    $page->wait(0.3);

    $page->click('[data-test="submit-button"]');
    $page->wait(3);

    // ===== CREATE 3ER AÑO =====
    $page = visit('/admin/grades/create');
    $page->wait(2);

    $page->click('[data-test="academic-year-select-trigger"]');
    $page->wait(1);
    $page->assertSee($academicYearName);
    $page->click('[role="option"]:has-text("'.$academicYearName.'")');
    $page->wait(1);

    $page->click('[data-test="grade-definition-select-trigger"]');
    $page->wait(0.5);
    $page->click('[role="option"]:has-text("3er Año")');
    $page->wait(0.5);

    $page->clear('[data-test="grade-order-input"]');
    $page->type('[data-test="grade-order-input"]', '3');
    $page->wait(0.3);

    $page->click('[data-test="submit-button"]');
    $page->wait(3);

    // Verify grades appear in listing
    $page = visit('/admin/grades?academic_year_id='.$academicYear->id);
    $page->wait(2);
    $page->assertPathIs('/admin/grades');
    $page->assertSee('1er Año');
    $page->assertSee('2do Año');
    $page->assertSee('3er Año');
});

// ============================================================================
// PASO 5: Crear Secciones
// ============================================================================

test('admin puede crear secciones para los grados', function () {
    $this->actingAs($this->admin);

    $academicYearName = 'Secciones-Test-'.uniqid();

    $academicYear = AcademicYear::factory()->create([
        'name' => $academicYearName,
        'is_active' => true,
    ]);

    $grade = Grade::factory()->for($academicYear)->create([
        'name' => '1er Año',
        'order' => 1,
    ]);

    // ===== CREATE SECCIÓN A =====
    $page = visit('/admin/sections/create');
    $page->wait(2);
    $page->assertSee('Nueva Sección');

    // Select academic year
    $page->click('[data-test="academic-year-select-trigger"]');
    $page->wait(1);
    $page->assertSee($academicYearName);
    $page->click('[role="option"]:has-text("'.$academicYearName.'")');
    $page->wait(1);

    // Select grade
    $page->click('[data-test="grade-select-trigger"]');
    $page->wait(1);
    $page->assertSee('1er Año');
    $page->click('[role="option"]:has-text("1er Año")');
    $page->wait(1);

    // Select section definition
    $page->click('[data-test="section-definition-select-trigger"]');
    $page->wait(0.5);
    $page->click('[role="option"]:has-text("A")');
    $page->wait(0.5);

    // Submit
    $page->click('[data-test="submit-button"]');
    $page->wait(3);

    // ===== CREATE SECCIÓN B =====
    $page = visit('/admin/sections/create');
    $page->wait(2);

    $page->click('[data-test="academic-year-select-trigger"]');
    $page->wait(1);
    $page->assertSee($academicYearName);
    $page->click('[role="option"]:has-text("'.$academicYearName.'")');
    $page->wait(1);

    $page->click('[data-test="grade-select-trigger"]');
    $page->wait(1);
    $page->assertSee('1er Año');
    $page->click('[role="option"]:has-text("1er Año")');
    $page->wait(1);

    $page->click('[data-test="section-definition-select-trigger"]');
    $page->wait(0.5);
    $page->click('[role="option"]:has-text("B")');
    $page->wait(0.5);

    $page->click('[data-test="submit-button"]');
    $page->wait(3);

    // Verify sections appear in listing
    $page = visit('/admin/sections?academic_year_id='.$academicYear->id);
    $page->wait(2);
    $page->assertPathIs('/admin/sections');
    $page->assertSee('Sección A');
    $page->assertSee('Sección B');
});

// ============================================================================
// PASO 6: Crear Usuarios (profesor + alumno)
// ============================================================================

test('admin puede crear usuarios profesor y alumno', function () {
    $this->actingAs($this->admin);

    // ===== CREATE TEACHER =====
    $page = visit('/admin/users/create');
    $page->wait(2);
    $page->assertSee('Nuevo Usuario');

    // Select role: profesor
    $page->click('[data-test="role-checkbox-profesor"]');
    $page->wait(0.3);

    // Fill teacher data
    $page->type('[data-test="cedula-input"]', '12345678');
    $page->wait(0.2);
    $page->type('[data-test="name-input"]', 'Prof. María García');
    $page->wait(0.2);
    $page->type('[data-test="email-input"]', 'maria.garcia@school.com');
    $page->wait(0.2);
    $page->type('[data-test="phone-input"]', '0414-1234567');
    $page->wait(0.2);
    $page->type('[data-test="address-input"]', 'Caracas');
    $page->wait(0.2);
    $page->type('[data-test="password-input"]', 'password');
    $page->wait(0.2);
    $page->type('[data-test="password-confirmation-input"]', 'password');
    $page->wait(0.2);

    // Submit
    $page->click('[data-test="submit-button"]');
    $page->wait(3);

    // ===== CREATE STUDENT =====
    $page = visit('/admin/users/create');
    $page->wait(2);

    // Select role: alumno
    $page->click('[data-test="role-checkbox-alumno"]');
    $page->wait(0.3);

    // Fill student data
    $page->type('[data-test="cedula-input"]', '87654321');
    $page->wait(0.2);
    $page->type('[data-test="name-input"]', 'Carlos Estudiante');
    $page->wait(0.2);
    $page->type('[data-test="email-input"]', 'carlos@student.com');
    $page->wait(0.2);
    $page->type('[data-test="phone-input"]', '0414-7654321');
    $page->wait(0.2);
    $page->type('[data-test="address-input"]', 'Caracas');
    $page->wait(0.2);
    $page->type('[data-test="password-input"]', 'password');
    $page->wait(0.2);
    $page->type('[data-test="password-confirmation-input"]', 'password');
    $page->wait(0.2);

    // Submit
    $page->click('[data-test="submit-button"]');
    $page->wait(3);

    // Verify users appear in listing
    $page = visit('/admin/users');
    $page->wait(2);
    $page->assertPathIs('/admin/users');
    $page->assertSee('Prof. María García');
    $page->assertSee('Carlos Estudiante');
});

// ============================================================================
// PASO 7: Inscribir alumno en sección
// ============================================================================

test('admin puede inscribir alumno en una sección', function () {
    $this->actingAs($this->admin);

    $academicYearName = 'Inscripciones-Test-'.uniqid();

    $academicYear = AcademicYear::factory()->create([
        'name' => $academicYearName,
        'is_active' => true,
    ]);

    $grade = Grade::factory()->for($academicYear)->create(['name' => '1er Año']);
    $section = Section::factory()->for($academicYear)->for($grade)->create(['name' => 'Sección A']);

    $student = User::factory()->create(['name' => 'Ana López']);
    $student->assignRole('alumno');

    // Navigate to enrollment create page
    $page = visit('/admin/enrollments/create');
    $page->wait(2);
    $page->assertSee('Nuevo Ingreso');
    $page->assertSee('Ana López'); // Student should appear in available list

    // Select the student using checkbox
    $page->click('[data-test="student-checkbox-'.$student->id.'"]');
    $page->wait(0.5);

    // Verify student is selected (badge should show "1 seleccionados")
    $page->assertSee('1 seleccionados');

    // Select destination grade
    $page->click('[data-test="grade-select-trigger"]');
    $page->wait(0.5);
    $page->click('[role="option"]:has-text("1er Año")');
    $page->wait(0.5);

    // Click on section button to enroll (opens confirmation dialog)
    $page->click('[data-test="enroll-to-section-'.$section->id.'"]');
    $page->wait(1);

    // Confirm enrollment in the AlertDialog
    $page->click('[data-test="confirm-enrollment-button"]');
    $page->wait(3);

    // Verify enrollment appears in section detail
    $page = visit("/admin/sections/{$section->id}");
    $page->wait(2);
    $page->assertSee('Ana López');
});

// ============================================================================
// PASO 8: Asignar profesor a sección
// ============================================================================

test('admin puede asignar profesor a una sección', function () {
    $this->actingAs($this->admin);

    $academicYearName = 'Asignaciones-Test-'.uniqid();

    $academicYear = AcademicYear::factory()->create([
        'name' => $academicYearName,
        'is_active' => true,
    ]);

    $grade = Grade::factory()->for($academicYear)->create(['name' => '1er Año']);
    $section = Section::factory()->for($academicYear)->for($grade)->create(['name' => 'Sección A']);

    $teacher = User::factory()->create(['name' => 'Prof. José Martínez']);
    $teacher->assignRole('profesor');

    // Navigate to teacher assignments page
    $page = visit('/admin/teacher-assignments/create');
    $page->wait(2);
    $page->assertSee('Asignación Docente');
    $page->assertSee('Prof. José Martínez'); // Teacher should appear in list

    // Select the teacher by clicking on their card
    $page->click('[data-test="teacher-item-'.$teacher->id.'"]');
    $page->wait(1);

    // Verify sections grid is visible
    $page->assertSee('1er Año');
    $page->assertSee('A');

    // Select the section by clicking on its card
    $page->click('[data-test="section-checkbox-'.$section->id.'"]');
    $page->wait(0.5);

    // Click save button (opens confirmation dialog)
    $page->click('[data-test="save-assignments-button"]');
    $page->wait(1);

    // Confirm in the AlertDialog
    $page->click('[data-test="confirm-save-button"]');
    $page->wait(3);

    // Verify assignment appears in section detail
    $page = visit("/admin/sections/{$section->id}");
    $page->wait(2);
    $page->assertSee('Prof. José Martínez');
});

// ============================================================================
// INTEGRACIÓN: Flujo completo secuencial
// ============================================================================

test('admin puede configurar toda la estructura académica en secuencia', function () {
    $this->actingAs($this->admin);

    $academicYearName = 'Secuencia-Test-'.uniqid();

    // 1. Create academic year via browser
    $page = visit('/admin/academic-years/create');
    $page->wait(2);
    $page->type('#name', $academicYearName);
    $page->wait(0.5);
    $page->type('#start_date', '2026-09-15');
    $page->wait(0.5);
    $page->type('#end_date', '2027-07-15');
    $page->wait(0.5);
    $page->clear('#required_hours');
    $page->type('#required_hours', '600');
    $page->wait(0.5);
    $page->click('button#is_active');
    $page->wait(1);
    $page->click('button[type="submit"]');
    $page->wait(5);
    $page->assertPathIs('/admin/academic-years');
    $page->assertSee($academicYearName);

    $academicYear = AcademicYear::where('name', $academicYearName)->first();
    expect($academicYear)->not->toBeNull();

    // 2. Create school terms via factory (browser tests can't use $this->post())
    $termType1 = TermType::where('name', 'Lapso 1')->first();
    $termType2 = TermType::where('name', 'Lapso 2')->first();
    $termType3 = TermType::where('name', 'Lapso 3')->first();

    SchoolTerm::factory()->create([
        'academic_year_id' => $academicYear->id,
        'term_type_id' => $termType1->id,
        'term_type_name' => $termType1->name,
        'start_date' => '2026-09-15',
        'end_date' => '2026-12-15',
    ]);

    SchoolTerm::factory()->create([
        'academic_year_id' => $academicYear->id,
        'term_type_id' => $termType2->id,
        'term_type_name' => $termType2->name,
        'start_date' => '2027-01-15',
        'end_date' => '2027-04-15',
    ]);

    SchoolTerm::factory()->create([
        'academic_year_id' => $academicYear->id,
        'term_type_id' => $termType3->id,
        'term_type_name' => $termType3->name,
        'start_date' => '2027-04-20',
        'end_date' => '2027-07-15',
    ]);

    // 3. Create grades via factory (using definitions)
    $grade1Def = GradeDefinition::where('name', '1er Año')->first();
    $grade1 = Grade::factory()->create([
        'academic_year_id' => $academicYear->id,
        'grade_definition_id' => $grade1Def->id,
        'order' => 1,
    ]);

    $grade2Def = GradeDefinition::where('name', '2do Año')->first();
    Grade::factory()->create([
        'academic_year_id' => $academicYear->id,
        'grade_definition_id' => $grade2Def->id,
        'order' => 2,
    ]);

    // 4. Create sections via factory (using definitions)
    $sectionADef = SectionDefinition::where('name', 'A')->first();
    $sectionA = Section::factory()->create([
        'academic_year_id' => $academicYear->id,
        'grade_id' => $grade1->id,
        'section_definition_id' => $sectionADef->id,
    ]);

    $sectionBDef = SectionDefinition::where('name', 'B')->first();
    Section::factory()->create([
        'academic_year_id' => $academicYear->id,
        'grade_id' => $grade1->id,
        'section_definition_id' => $sectionBDef->id,
    ]);

    // 5. Create teacher and student
    $teacher = User::factory()->create(['name' => 'Prof. Happy Path']);
    $teacher->assignRole('profesor');

    $student = User::factory()->create(['name' => 'Alumno Happy Path']);
    $student->assignRole('alumno');

    // 6. Enroll student via factory
    Enrollment::factory()->create([
        'user_id' => $student->id,
        'section_id' => $sectionA->id,
        'academic_year_id' => $academicYear->id,
        'grade_id' => $grade1->id,
    ]);

    // 7. Assign teacher to section via factory
    TeacherAssignment::factory()->create([
        'user_id' => $teacher->id,
        'section_id' => $sectionA->id,
        'academic_year_id' => $academicYear->id,
    ]);

    // 8. Verify the academic structure overview page
    $page = visit('/admin/academic-info');
    $page->wait(2);
    $page->assertPathIs('/admin/academic-info');
});
