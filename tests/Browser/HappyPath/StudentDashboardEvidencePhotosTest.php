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

    $this->academicYear = AcademicYear::factory()->create([
        'name' => '2024-2025-DB-EVID',
        'is_active' => true,
        'required_hours' => 600,
    ]);

    $this->grade = Grade::factory()->for($this->academicYear)->create(['name' => '1er Año']);
    $this->section = Section::factory()->for($this->academicYear)->for($this->grade)->create(['name' => 'A']);

    $this->student = User::factory()->create(['name' => 'Diana Evidencias']);
    $this->student->assignRole('alumno');

    Enrollment::factory()
        ->for($this->academicYear)
        ->for($this->grade)
        ->for($this->section)
        ->for($this->student, 'student')
        ->create();

    $teacher = User::factory()->create(['name' => 'Prof. Evidencias']);
    $teacher->assignRole('profesor');
    $realizedStatus = FieldSessionStatus::where('name', 'realized')->first();

    $this->fieldSession = FieldSession::factory()->create([
        'name' => 'Jornada de Evidencias Dashboard',
        'academic_year_id' => $this->academicYear->id,
        'user_id' => $teacher->id,
        'status_id' => $realizedStatus->id,
        'base_hours' => 2.0,
        'start_datetime' => now()->subDay(),
        'end_datetime' => now()->subDay()->addHours(2),
    ]);

    $this->activityCategory = ActivityCategory::factory()->create(['name' => 'Arte y Cultura']);

    $attendance = Attendance::factory()->create([
        'field_session_id' => $this->fieldSession->id,
        'user_id' => $this->student->id,
        'academic_year_id' => $this->academicYear->id,
        'attended' => true,
    ]);

    $this->activity = AttendanceActivity::factory()->create([
        'attendance_id' => $attendance->id,
        'activity_category_id' => $this->activityCategory->id,
        'hours' => 2.0,
    ]);

    // Attach one media evidence photo
    $this->activity->addMedia(base_path('tests/fixtures/sample-image.jpg'))
        ->preservingOriginal()
        ->toMediaCollection('evidence_photos');
});

test('estudiante ve badge con indicador de fotos en actividades del dashboard', function () {
    $this->actingAs($this->student);

    $page = visit('/dashboard');

    $page->assertPathIs('/dashboard')
        ->assertSee('Arte y Cultura')
        ->assertSee('2.0')
        ->assertSee('Jornada de Evidencias Dashboard')
        ->assertNoJavaScriptErrors();
});

test('estudiante puede abrir galería de evidencias desde badge en dashboard', function () {
    $this->actingAs($this->student);

    $page = visit('/dashboard');

    // The badge with evidence photos should be clickable and open gallery
    $page->click('[data-testid^="activity-badge-evidence-"]')
        ->waitForText('1 / 1')
        ->assertSee('1 / 1')
        ->assertNoJavaScriptErrors();
});
