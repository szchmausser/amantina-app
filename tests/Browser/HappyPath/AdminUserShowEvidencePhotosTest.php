<?php

use App\Models\AcademicYear;
use App\Models\ActivityCategory;
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

    $this->admin = User::factory()->create(['email' => 'admin@evidencia-test.com']);
    $this->admin->assignRole('admin');

    $this->actingAs($this->admin);

    $this->academicYear = AcademicYear::factory()->create([
        'name' => '2024-2025-ADM-EV',
        'is_active' => true,
        'required_hours' => 600,
    ]);

    $this->grade = Grade::factory()->for($this->academicYear)->create(['name' => '1er Año']);
    $this->section = Section::factory()->for($this->academicYear)->for($this->grade)->create(['name' => 'A']);

    $this->student = User::factory()->create(['name' => 'Estudiante Admin Evidencias']);
    $this->student->assignRole('alumno');

    Enrollment::factory()
        ->for($this->academicYear)
        ->for($this->grade)
        ->for($this->section)
        ->for($this->student, 'student')
        ->create();

    $teacher = User::factory()->create(['name' => 'Prof. Admin Evid']);
    $teacher->assignRole('profesor');
    $realizedStatus = FieldSessionStatus::where('name', 'realized')->first();

    $this->fieldSession = FieldSession::factory()->create([
        'name' => 'Jornada Admin Evidencias',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $teacher->id,
        'status_id' => $realizedStatus->id,
        'base_hours' => 3.0,
        'start_datetime' => now()->subDay(),
        'end_datetime' => now()->subDay()->addHours(3),
    ]);

    $this->activityCategory = ActivityCategory::factory()->create(['name' => 'Deporte']);

    $attendance = Attendance::factory()->create([
        'field_session_id' => $this->fieldSession->id,
        'user_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    $this->activity = AttendanceActivity::factory()->create([
        'attendance_id' => $attendance->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 3.0,
    ]);

    // Attach media evidence
    $this->activity->addMedia(base_path('tests/fixtures/sample-image.jpg'))
        ->preservingOriginal()
        ->toMediaCollection('evidence_photos');
});

test('admin ve badge con indicador de fotos en horas del estudiante', function () {
    $page = visit("/admin/users/{$this->student->id}");

    $page->assertSee('Estudiante Admin Evidencias')
        ->assertNoJavaScriptErrors();

    // Navigate to hours tab — the Radix TabsTrigger renders as a button with the tab text
    $page->click('button:has-text("Horas")')
        ->waitForText('Historial de Horas Socioproductivas')
        ->assertSee('Deporte')
        ->assertNoJavaScriptErrors();
});

test('admin puede abrir galería de evidencias desde badge en horas del estudiante', function () {
    $page = visit("/admin/users/{$this->student->id}");

    // Go to hours tab
    $page->click('button:has-text("Horas")')
        ->waitForText('Historial de Horas Socioproductivas');

    // Click on evidence badge to open gallery
    $page->click('[data-testid^="activity-badge-evidence-"]')
        ->waitForText('1 / 1')
        ->assertSee('1 / 1')
        ->assertNoJavaScriptErrors();
});
