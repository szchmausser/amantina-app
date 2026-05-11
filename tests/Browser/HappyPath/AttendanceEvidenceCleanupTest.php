<?php

namespace Tests\Browser\HappyPath;

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

    $this->admin = User::factory()->create([
        'email' => 'admin@test.com',
        'password' => bcrypt('password'),
    ]);
    $this->admin->assignRole('admin');

    $this->profesor = User::factory()->create(['name' => 'Prof. Martínez']);
    $this->profesor->assignRole('profesor');

    $this->actingAs($this->admin);

    $this->academicYear = AcademicYear::factory()->create([
        'name' => '2024-2025',
        'is_active' => true,
    ]);

    $this->grade = Grade::factory()->for($this->academicYear)->create(['name' => '1er Año']);
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

    $this->activityCategory = ActivityCategory::factory()->create(['name' => 'Agricultura']);
});

test('modal de actividades precarga fila para estudiante sin actividades', function () {
    Attendance::factory()->create([
        'field_session_id' => $this->fieldSession->id,
        'user_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    $page = visit("/admin/field-sessions/{$this->fieldSession->id}");

    $page->assertSee('Luis Estudiante')
        ->assertNoJavaScriptErrors();

    $page->click("[data-testid=\"btn-activities-{$this->student->id}\"]")
        ->waitForText('Detalle de Actividades');

    // Should NOT show "No hay actividades registradas"
    $page->assertDontSee('No hay actividades registradas');

    // Should show at least one activity row with inputs
    $page->assertPresent('[data-testid^="activity-photos-input-new-"]')
        ->assertNoJavaScriptErrors();
});

test('botón de horas globales ya no existe en la interfaz', function () {
    Attendance::factory()->create([
        'field_session_id' => $this->fieldSession->id,
        'user_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    $page = visit("/admin/field-sessions/{$this->fieldSession->id}");

    $page->assertSee('Luis Estudiante')
        ->assertNoJavaScriptErrors();

    // The global hours button should NOT be present
    $page->assertMissing("[data-testid=\"btn-global-hours-{$this->student->id}\"]");
});

test('modal de actividades separa existentes de nuevas visualmente', function () {
    $attendance = Attendance::factory()->create([
        'field_session_id' => $this->fieldSession->id,
        'user_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    AttendanceActivity::factory()->create([
        'attendance_id' => $attendance->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 1.5,
    ]);

    $page = visit("/admin/field-sessions/{$this->fieldSession->id}");

    $page->click("[data-testid=\"btn-activities-{$this->student->id}\"]")
        ->waitForText('Detalle de Actividades')
        ->click('button:has-text("Agregar Actividad")')
        ->waitForText('Nuevas actividades');

    $page->assertPresent('[data-testid="existing-activities-section"]')
        ->assertPresent('[data-testid="new-activities-section"]')
        ->assertPresent('[data-testid="activities-separator"]')
        ->assertSee('Actividades registradas')
        ->assertSee('Nuevas actividades')
        ->assertNoJavaScriptErrors();
});

test('click en badge de actividad abre galería de evidencias', function () {
    $attendance = Attendance::factory()->create([
        'field_session_id' => $this->fieldSession->id,
        'user_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    $activity = AttendanceActivity::factory()->create([
        'attendance_id' => $attendance->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 1.5,
    ]);

    $activity->addMedia(base_path('tests/fixtures/sample-image.jpg'))
        ->preservingOriginal()
        ->toMediaCollection('evidence_photos');

    $page = visit("/admin/field-sessions/{$this->fieldSession->id}");

    $page->assertSee('Luis Estudiante')
        ->assertNoJavaScriptErrors();

    $page->click("[data-testid=\"activity-badge-evidence-{$activity->id}\"]")
        ->waitForText('1 / 1')
        ->assertSee('1 / 1')
        ->assertNoJavaScriptErrors();
});
