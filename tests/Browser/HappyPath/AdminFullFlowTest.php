<?php

require_once __DIR__.'/../Helpers.php';

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\SchoolTerm;
use App\Models\Section;
use App\Models\TeacherAssignment;
use App\Models\TermType;
use App\Models\User;
use Database\Seeders\FieldSessionStatusSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
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

    $this->admin = User::factory()->create([
        'email' => 'admin@happytest.com',
        'password' => bcrypt('password'),
    ]);
    $this->admin->assignRole('admin');

    // NOTE: Do NOT generate academic year name here - RefreshDatabase hasn't run yet
    // Each test will generate its own unique name when needed

    // NOTE: actingAs() is NOT called here intentionally.
    // The login test needs to start without an active session so Fortify's guest
    // middleware does not redirect /login → /dashboard before #email renders.
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

    // Generate unique academic year name AFTER RefreshDatabase has run
    $testAcademicYearName = generateUniqueAcademicYearName();

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

    // Verify in database
    $this->assertDatabaseHas('academic_years', [
        'name' => $testAcademicYearName,
        'required_hours' => 600,
        'is_active' => true,
    ]);
});

// ============================================================================
// PASO 3: Crear Lapsos Académicos
// ============================================================================

test('admin puede crear lapsos académicos para el año escolar', function () {
    $this->actingAs($this->admin);

    // Generate unique sequential academic year name
    $academicYearName = generateUniqueAcademicYearName();

    // Create academic year with explicit dates that match our term dates
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

    // Verify Lapso 1 was created
    $this->assertDatabaseHas('school_terms', [
        'academic_year_id' => $academicYear->id,
        'term_type_id' => 1,
        'start_date' => '2025-09-01',
        'end_date' => '2025-12-15',
    ]);

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

    // Verify Lapso 2 was created
    $this->assertDatabaseHas('school_terms', [
        'academic_year_id' => $academicYear->id,
        'term_type_id' => 2,
        'start_date' => '2026-01-15',
        'end_date' => '2026-04-15',
    ]);

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

    // Verify Lapso 3 was created
    $this->assertDatabaseHas('school_terms', [
        'academic_year_id' => $academicYear->id,
        'term_type_id' => 3,
        'start_date' => '2026-04-20',
        'end_date' => '2026-07-15',
    ]);

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

    // Generate unique sequential academic year name
    $academicYearName = generateUniqueAcademicYearName();

    $academicYear = AcademicYear::factory()->create([
        'name' => $academicYearName,
        'is_active' => true,
    ]);

    // ===== CREATE 1ER AÑO =====
    $page = visit('/admin/grades/create');
    $page->wait(2);
    $page->assertSee('Nuevo Grado Académico');

    // Select academic year - with more explicit waits
    $page->click('[data-test="academic-year-select-trigger"]');
    $page->wait(1); // Increased wait for dropdown to open
    $page->assertSee($academicYearName); // Verify option is visible
    $page->click('[role="option"]:has-text("'.$academicYearName.'")');
    $page->wait(1); // Increased wait for selection to complete

    // Fill name
    $page->type('[data-test="grade-name-input"]', '1er Año');
    $page->wait(0.3);

    // Fill order
    $page->clear('[data-test="grade-order-input"]');
    $page->type('[data-test="grade-order-input"]', '1');
    $page->wait(0.3);

    // Submit
    $page->click('[data-test="submit-button"]');
    $page->wait(3);

    // Verify 1er Año was created
    $this->assertDatabaseHas('grades', [
        'academic_year_id' => $academicYear->id,
        'name' => '1er Año',
        'order' => 1,
    ]);

    // ===== CREATE 2DO AÑO =====
    $page = visit('/admin/grades/create');
    $page->wait(2);

    // Select academic year - with more explicit waits
    $page->click('[data-test="academic-year-select-trigger"]');
    $page->wait(1);
    $page->assertSee($academicYearName);
    $page->click('[role="option"]:has-text("'.$academicYearName.'")');
    $page->wait(1);

    // Fill name
    $page->type('[data-test="grade-name-input"]', '2do Año');
    $page->wait(0.3);

    // Fill order
    $page->clear('[data-test="grade-order-input"]');
    $page->type('[data-test="grade-order-input"]', '2');
    $page->wait(0.3);

    // Submit
    $page->click('[data-test="submit-button"]');
    $page->wait(3);

    // Verify 2do Año was created
    $this->assertDatabaseHas('grades', [
        'academic_year_id' => $academicYear->id,
        'name' => '2do Año',
        'order' => 2,
    ]);

    // ===== CREATE 3ER AÑO =====
    $page = visit('/admin/grades/create');
    $page->wait(2);

    // Select academic year - with more explicit waits
    $page->click('[data-test="academic-year-select-trigger"]');
    $page->wait(1);
    $page->assertSee($academicYearName);
    $page->click('[role="option"]:has-text("'.$academicYearName.'")');
    $page->wait(1);

    // Fill name
    $page->type('[data-test="grade-name-input"]', '3er Año');
    $page->wait(0.3);

    // Fill order
    $page->clear('[data-test="grade-order-input"]');
    $page->type('[data-test="grade-order-input"]', '3');
    $page->wait(0.3);

    // Submit
    $page->click('[data-test="submit-button"]');
    $page->wait(3);

    // Verify 3er Año was created
    $this->assertDatabaseHas('grades', [
        'academic_year_id' => $academicYear->id,
        'name' => '3er Año',
        'order' => 3,
    ]);

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

    // Generate unique sequential academic year name
    $academicYearName = generateUniqueAcademicYearName();

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

    // Fill name
    $page->type('[data-test="section-name-input"]', 'Sección A');
    $page->wait(0.3);

    // Submit
    $page->click('[data-test="submit-button"]');
    $page->wait(3);

    // Verify Sección A was created
    $this->assertDatabaseHas('sections', [
        'grade_id' => $grade->id,
        'academic_year_id' => $academicYear->id,
        'name' => 'Sección A',
    ]);

    // ===== CREATE SECCIÓN B =====
    $page = visit('/admin/sections/create');
    $page->wait(2);

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

    // Fill name
    $page->type('[data-test="section-name-input"]', 'Sección B');
    $page->wait(0.3);

    // Submit
    $page->click('[data-test="submit-button"]');
    $page->wait(3);

    // Verify Sección B was created
    $this->assertDatabaseHas('sections', [
        'grade_id' => $grade->id,
        'name' => 'Sección B',
    ]);

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

    // Verify teacher was created
    $this->assertDatabaseHas('users', [
        'cedula' => '12345678',
        'name' => 'Prof. María García',
        'email' => 'maria.garcia@school.com',
    ]);

    // Verify the teacher has the role
    $teacher = User::where('cedula', '12345678')->first();
    expect($teacher->hasRole('profesor'))->toBeTrue();

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

    // Verify student was created
    $this->assertDatabaseHas('users', [
        'cedula' => '87654321',
        'name' => 'Carlos Estudiante',
        'email' => 'carlos@student.com',
    ]);

    $student = User::where('cedula', '87654321')->first();
    expect($student->hasRole('alumno'))->toBeTrue();

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

    // Generate unique sequential academic year name
    $academicYearName = generateUniqueAcademicYearName();

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
    $page->wait(1); // Wait for dialog to open

    // Confirm enrollment in the AlertDialog
    $page->click('[data-test="confirm-enrollment-button"]');
    $page->wait(3); // Wait for enrollment to complete

    // Verify enrollment in database
    $this->assertDatabaseHas('enrollments', [
        'user_id' => $student->id,
        'academic_year_id' => $academicYear->id,
        'grade_id' => $grade->id,
        'section_id' => $section->id,
    ]);

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

    // Generate unique sequential academic year name
    $academicYearName = generateUniqueAcademicYearName();

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
    $page->wait(1); // Wait for sections grid to load

    // Verify sections grid is visible
    $page->assertSee('1er Año');
    $page->assertSee('A');

    // Select the section by clicking on its card
    $page->click('[data-test="section-checkbox-'.$section->id.'"]');
    $page->wait(0.5);

    // Click save button (opens confirmation dialog)
    $page->click('[data-test="save-assignments-button"]');
    $page->wait(1); // Wait for dialog to open

    // Confirm in the AlertDialog
    $page->click('[data-test="confirm-save-button"]');
    $page->wait(3); // Wait for save to complete

    // Verify assignment in database
    $this->assertDatabaseHas('teacher_assignments', [
        'user_id' => $teacher->id,
        'section_id' => $section->id,
        'academic_year_id' => $academicYear->id,
    ]);
});

// ============================================================================
// INTEGRACIÓN: Flujo completo secuencial
// ============================================================================

test('admin puede configurar toda la estructura académica en secuencia', function () {
    $this->actingAs($this->admin);

    // Generate unique sequential academic year name
    $academicYearName = generateUniqueAcademicYearName();

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

    expect($academicYear->fresh()->schoolTerms)->toHaveCount(3);

    // 3. Create grades via factory
    $grade1 = Grade::factory()->create([
        'academic_year_id' => $academicYear->id,
        'name' => '1er Año',
        'order' => 1,
    ]);

    $grade2 = Grade::factory()->create([
        'academic_year_id' => $academicYear->id,
        'name' => '2do Año',
        'order' => 2,
    ]);

    expect($academicYear->fresh()->grades)->toHaveCount(2);

    // 4. Create sections via factory
    $sectionA = Section::factory()->create([
        'academic_year_id' => $academicYear->id,
        'grade_id' => $grade1->id,
        'name' => 'Sección A',
    ]);

    $sectionB = Section::factory()->create([
        'academic_year_id' => $academicYear->id,
        'grade_id' => $grade1->id,
        'name' => 'Sección B',
    ]);

    expect($grade1->fresh()->sections)->toHaveCount(2);

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

    expect($sectionA->fresh()->enrollments)->toHaveCount(1);

    // 7. Assign teacher to section via factory
    TeacherAssignment::factory()->create([
        'user_id' => $teacher->id,
        'section_id' => $sectionA->id,
        'academic_year_id' => $academicYear->id,
    ]);

    expect($sectionA->fresh()->teacherAssignments)->toHaveCount(1);

    // 8. Verify the academic structure overview page
    $page = visit('/admin/academic-info');
    $page->wait(2);
    $page->assertPathIs('/admin/academic-info');
});
