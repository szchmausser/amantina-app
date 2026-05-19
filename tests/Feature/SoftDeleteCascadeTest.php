<?php

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\AttendanceActivity;
use App\Models\Enrollment;
use App\Models\FieldSession;
use App\Models\Grade;
use App\Models\GradeDefinition;
use App\Models\HealthCondition;
use App\Models\SchoolTerm;
use App\Models\Section;
use App\Models\SectionDefinition;
use App\Models\StudentHealthRecord;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Models\FieldSessionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // No seeding needed — these are model-level tests, not controller tests.
});

// ──────────────────────────────────────────────
// CASCADE RESTORE: AcademicYear → SchoolTerm
// ──────────────────────────────────────────────

test('restoring an academic year restores its soft-deleted school terms', function () {
    $year = AcademicYear::factory()->create(['name' => '2025-2026']);
    $term = SchoolTerm::factory()->for($year)->create();

    $year->delete();
    expect(SchoolTerm::withTrashed()->find($term->id)->trashed())->toBeTrue();

    $year->restore();

    expect(SchoolTerm::withTrashed()->find($term->id)->trashed())->toBeFalse();
});

// ──────────────────────────────────────────────
// CASCADE RESTORE: AcademicYear → Grade → Section
// ──────────────────────────────────────────────

test('restoring an academic year restores its soft-deleted grades', function () {
    $year = AcademicYear::factory()->create(['name' => '2025-2026']);
    $gradeDef = GradeDefinition::firstOrCreate(['name' => '1er Año']);
    $grade = Grade::create([
        'academic_year_id' => $year->id,
        'grade_definition_id' => $gradeDef->id,
        'grade_definition_name' => $gradeDef->name,
        'name' => $gradeDef->name,
        'order' => 1,
    ]);

    $year->delete();
    expect(Grade::withTrashed()->find($grade->id)->trashed())->toBeTrue();

    $year->restore();

    expect(Grade::withTrashed()->find($grade->id)->trashed())->toBeFalse();
});

test('restoring a grade restores its soft-deleted sections', function () {
    $year = AcademicYear::factory()->create(['name' => '2025-2026']);
    $gradeDef = GradeDefinition::firstOrCreate(['name' => '1er Año']);
    $grade = Grade::create([
        'academic_year_id' => $year->id,
        'grade_definition_id' => $gradeDef->id,
        'grade_definition_name' => $gradeDef->name,
        'name' => $gradeDef->name,
        'order' => 1,
    ]);
    $sectionDef = SectionDefinition::firstOrCreate(['name' => 'A']);
    $section = Section::create([
        'grade_id' => $grade->id,
        'academic_year_id' => $year->id,
        'section_definition_id' => $sectionDef->id,
        'section_definition_name' => $sectionDef->name,
        'name' => $sectionDef->name,
    ]);

    $grade->delete();
    expect(Section::withTrashed()->find($section->id)->trashed())->toBeTrue();

    $grade->restore();

    expect(Section::withTrashed()->find($section->id)->trashed())->toBeFalse();
});

test('restoring an academic year restores sections through the grade chain', function () {
    $year = AcademicYear::factory()->create(['name' => '2025-2026']);
    $gradeDef = GradeDefinition::firstOrCreate(['name' => '1er Año']);
    $grade = Grade::create([
        'academic_year_id' => $year->id,
        'grade_definition_id' => $gradeDef->id,
        'grade_definition_name' => $gradeDef->name,
        'name' => $gradeDef->name,
        'order' => 1,
    ]);
    $sectionDef = SectionDefinition::firstOrCreate(['name' => 'A']);
    $section = Section::create([
        'grade_id' => $grade->id,
        'academic_year_id' => $year->id,
        'section_definition_id' => $sectionDef->id,
        'section_definition_name' => $sectionDef->name,
        'name' => $sectionDef->name,
    ]);

    $year->delete();
    expect(Section::withTrashed()->find($section->id)->trashed())->toBeTrue();

    $year->restore();

    expect(Section::withTrashed()->find($section->id)->trashed())->toBeFalse();
});

// ──────────────────────────────────────────────
// CASCADE RESTORE: Section → Enrollment + TeacherAssignment
// ──────────────────────────────────────────────

test('restoring a section restores its soft-deleted enrollments', function () {
    $year = AcademicYear::factory()->create(['name' => '2025-2026']);
    $gradeDef = GradeDefinition::firstOrCreate(['name' => '1er Año']);
    $grade = Grade::create([
        'academic_year_id' => $year->id,
        'grade_definition_id' => $gradeDef->id,
        'grade_definition_name' => $gradeDef->name,
        'name' => $gradeDef->name,
        'order' => 1,
    ]);
    $sectionDef = SectionDefinition::firstOrCreate(['name' => 'A']);
    $section = Section::create([
        'grade_id' => $grade->id,
        'academic_year_id' => $year->id,
        'section_definition_id' => $sectionDef->id,
        'section_definition_name' => $sectionDef->name,
        'name' => $sectionDef->name,
    ]);
    $student = User::factory()->create();
    $enrollment = Enrollment::create([
        'user_id' => $student->id,
        'section_id' => $section->id,
        'academic_year_id' => $year->id,
        'grade_id' => $grade->id,
    ]);

    $section->delete();
    expect(Enrollment::withTrashed()->find($enrollment->id)->trashed())->toBeTrue();

    $section->restore();

    expect(Enrollment::withTrashed()->find($enrollment->id)->trashed())->toBeFalse();
});

test('restoring a section restores its soft-deleted teacher assignments', function () {
    $year = AcademicYear::factory()->create(['name' => '2025-2026']);
    $gradeDef = GradeDefinition::firstOrCreate(['name' => '1er Año']);
    $grade = Grade::create([
        'academic_year_id' => $year->id,
        'grade_definition_id' => $gradeDef->id,
        'grade_definition_name' => $gradeDef->name,
        'name' => $gradeDef->name,
        'order' => 1,
    ]);
    $sectionDef = SectionDefinition::firstOrCreate(['name' => 'A']);
    $section = Section::create([
        'grade_id' => $grade->id,
        'academic_year_id' => $year->id,
        'section_definition_id' => $sectionDef->id,
        'section_definition_name' => $sectionDef->name,
        'name' => $sectionDef->name,
    ]);
    $teacher = User::factory()->create();
    $assignment = TeacherAssignment::create([
        'user_id' => $teacher->id,
        'section_id' => $section->id,
        'academic_year_id' => $year->id,
        'grade_id' => $grade->id,
    ]);

    $section->delete();
    expect(TeacherAssignment::withTrashed()->find($assignment->id)->trashed())->toBeTrue();

    $section->restore();

    expect(TeacherAssignment::withTrashed()->find($assignment->id)->trashed())->toBeFalse();
});

// ──────────────────────────────────────────────
// CASCADE RESTORE: AcademicYear → FieldSession → Attendance → AttendanceActivity
// ──────────────────────────────────────────────

test('restoring an academic year restores its soft-deleted field sessions', function () {
    $year = AcademicYear::factory()->create(['name' => '2025-2026']);
    $teacher = User::factory()->create();
    $statusId = FieldSessionStatus::firstOrCreate(['name' => 'planned'])->id;
    $session = FieldSession::create([
        'academic_year_id' => $year->id,
        'user_id' => $teacher->id,
        'name' => 'Jornada de prueba',
        'start_datetime' => now(),
        'end_datetime' => now()->addHours(2),
        'base_hours' => 2,
        'status_id' => $statusId,
    ]);

    $year->delete();
    expect(FieldSession::withTrashed()->find($session->id)->trashed())->toBeTrue();

    $year->restore();

    expect(FieldSession::withTrashed()->find($session->id)->trashed())->toBeFalse();
});

test('restoring a field session restores its soft-deleted attendances', function () {
    $year = AcademicYear::factory()->create(['name' => '2025-2026']);
    $teacher = User::factory()->create();
    $statusId = FieldSessionStatus::firstOrCreate(['name' => 'planned'])->id;
    $session = FieldSession::create([
        'academic_year_id' => $year->id,
        'user_id' => $teacher->id,
        'name' => 'Jornada de prueba',
        'start_datetime' => now(),
        'end_datetime' => now()->addHours(2),
        'base_hours' => 2,
        'status_id' => $statusId,
    ]);
    $student = User::factory()->create();
    $attendance = Attendance::create([
        'field_session_id' => $session->id,
        'user_id' => $student->id,
        'academic_year_id' => $year->id,
        'attended' => true,
    ]);

    $session->delete();
    expect(Attendance::withTrashed()->find($attendance->id)->trashed())->toBeTrue();

    $session->restore();

    expect(Attendance::withTrashed()->find($attendance->id)->trashed())->toBeFalse();
});

test('restoring an attendance restores its soft-deleted attendance activities', function () {
    $year = AcademicYear::factory()->create(['name' => '2025-2026']);
    $teacher = User::factory()->create();
    $statusId = FieldSessionStatus::firstOrCreate(['name' => 'planned'])->id;
    $session = FieldSession::create([
        'academic_year_id' => $year->id,
        'user_id' => $teacher->id,
        'name' => 'Jornada de prueba',
        'start_datetime' => now(),
        'end_datetime' => now()->addHours(2),
        'base_hours' => 2,
        'status_id' => $statusId,
    ]);
    $student = User::factory()->create();
    $attendance = Attendance::create([
        'field_session_id' => $session->id,
        'user_id' => $student->id,
        'academic_year_id' => $year->id,
        'attended' => true,
    ]);
    $activity = AttendanceActivity::create([
        'attendance_id' => $attendance->id,
        'hours' => 2,
    ]);

    $attendance->delete();
    expect(AttendanceActivity::withTrashed()->find($activity->id)->trashed())->toBeTrue();

    $attendance->restore();

    expect(AttendanceActivity::withTrashed()->find($activity->id)->trashed())->toBeFalse();
});

// ──────────────────────────────────────────────
// CASCADE RESTORE: HealthCondition → StudentHealthRecord
// ──────────────────────────────────────────────

test('restoring a health condition restores its soft-deleted student health records', function () {
    $condition = HealthCondition::create(['name' => 'Asma', 'is_active' => true]);
    $student = User::factory()->create();
    $receiver = User::factory()->create();
    $record = StudentHealthRecord::create([
        'user_id' => $student->id,
        'health_condition_id' => $condition->id,
        'received_by' => $receiver->id,
        'received_at' => now(),
        'received_at_location' => 'Consultorio',
    ]);

    $condition->delete();
    expect(StudentHealthRecord::withTrashed()->find($record->id)->trashed())->toBeTrue();

    $condition->restore();

    expect(StudentHealthRecord::withTrashed()->find($record->id)->trashed())->toBeFalse();
});

// ──────────────────────────────────────────────
// FULL TREE RESTORE: AcademicYear restores everything
// ──────────────────────────────────────────────

test('restoring an academic year restores the entire tree of related records', function () {
    // Build full tree
    $year = AcademicYear::factory()->create(['name' => '2025-2026']);

    // AcademicYear → SchoolTerm
    $term = SchoolTerm::factory()->for($year)->create();

    // AcademicYear → Grade → Section → Enrollment + TeacherAssignment
    $gradeDef = GradeDefinition::firstOrCreate(['name' => '1er Año']);
    $grade = Grade::create([
        'academic_year_id' => $year->id,
        'grade_definition_id' => $gradeDef->id,
        'grade_definition_name' => $gradeDef->name,
        'name' => $gradeDef->name,
        'order' => 1,
    ]);
    $sectionDef = SectionDefinition::firstOrCreate(['name' => 'A']);
    $section = Section::create([
        'grade_id' => $grade->id,
        'academic_year_id' => $year->id,
        'section_definition_id' => $sectionDef->id,
        'section_definition_name' => $sectionDef->name,
        'name' => $sectionDef->name,
    ]);
    $student = User::factory()->create();
    $enrollment = Enrollment::create([
        'user_id' => $student->id,
        'section_id' => $section->id,
        'academic_year_id' => $year->id,
        'grade_id' => $grade->id,
    ]);
    $teacher = User::factory()->create();
    $assignment = TeacherAssignment::create([
        'user_id' => $teacher->id,
        'section_id' => $section->id,
        'academic_year_id' => $year->id,
        'grade_id' => $grade->id,
    ]);

    // AcademicYear → FieldSession → Attendance → AttendanceActivity
    $statusId = FieldSessionStatus::firstOrCreate(['name' => 'planned'])->id;
    $session = FieldSession::create([
        'academic_year_id' => $year->id,
        'user_id' => $teacher->id,
        'name' => 'Jornada de prueba',
        'start_datetime' => now(),
        'end_datetime' => now()->addHours(2),
        'base_hours' => 2,
        'status_id' => $statusId,
    ]);
    $attendance = Attendance::create([
        'field_session_id' => $session->id,
        'user_id' => $student->id,
        'academic_year_id' => $year->id,
        'attended' => true,
    ]);
    $activity = AttendanceActivity::create([
        'attendance_id' => $attendance->id,
        'hours' => 2,
    ]);

    // Soft delete the year
    $year->delete();

    // Verify everything is soft-deleted
    expect(SchoolTerm::withTrashed()->find($term->id)->trashed())->toBeTrue();
    expect(Grade::withTrashed()->find($grade->id)->trashed())->toBeTrue();
    expect(Section::withTrashed()->find($section->id)->trashed())->toBeTrue();
    expect(Enrollment::withTrashed()->find($enrollment->id)->trashed())->toBeTrue();
    expect(TeacherAssignment::withTrashed()->find($assignment->id)->trashed())->toBeTrue();
    expect(FieldSession::withTrashed()->find($session->id)->trashed())->toBeTrue();
    expect(Attendance::withTrashed()->find($attendance->id)->trashed())->toBeTrue();
    expect(AttendanceActivity::withTrashed()->find($activity->id)->trashed())->toBeTrue();

    // Restore the year — this should cascade through the entire tree
    $year->restore();

    // Verify everything is restored
    expect(SchoolTerm::withTrashed()->find($term->id)->trashed())->toBeFalse();
    expect(Grade::withTrashed()->find($grade->id)->trashed())->toBeFalse();
    expect(Section::withTrashed()->find($section->id)->trashed())->toBeFalse();
    expect(Enrollment::withTrashed()->find($enrollment->id)->trashed())->toBeFalse();
    expect(TeacherAssignment::withTrashed()->find($assignment->id)->trashed())->toBeFalse();
    expect(FieldSession::withTrashed()->find($session->id)->trashed())->toBeFalse();
    expect(Attendance::withTrashed()->find($attendance->id)->trashed())->toBeFalse();
    expect(AttendanceActivity::withTrashed()->find($activity->id)->trashed())->toBeFalse();
});

// ──────────────────────────────────────────────
// PARTIAL RESTORE: Restoring a grade does NOT restore the parent AcademicYear
// ──────────────────────────────────────────────

test('restoring a grade restores its sections but not its parent academic year', function () {
    $year = AcademicYear::factory()->create(['name' => '2025-2026']);
    $gradeDef = GradeDefinition::firstOrCreate(['name' => '1er Año']);
    $grade = Grade::create([
        'academic_year_id' => $year->id,
        'grade_definition_id' => $gradeDef->id,
        'grade_definition_name' => $gradeDef->name,
        'name' => $gradeDef->name,
        'order' => 1,
    ]);
    $sectionDef = SectionDefinition::firstOrCreate(['name' => 'A']);
    $section = Section::create([
        'grade_id' => $grade->id,
        'academic_year_id' => $year->id,
        'section_definition_id' => $sectionDef->id,
        'section_definition_name' => $sectionDef->name,
        'name' => $sectionDef->name,
    ]);

    // Delete everything
    $year->delete();
    expect($year->fresh()->trashed())->toBeTrue();
    expect($grade->fresh()->trashed())->toBeTrue();
    expect($section->fresh()->trashed())->toBeTrue();

    // Restore only the grade (not the year)
    $grade->restore();

    // Grade and section should be restored
    expect($grade->fresh()->trashed())->toBeFalse();
    expect($section->fresh()->trashed())->toBeFalse();

    // But the parent year should still be deleted
    expect($year->fresh()->trashed())->toBeTrue();
});

// ──────────────────────────────────────────────
// UNIQUE CONSTRAINT WITH SOFT DELETE: Can reuse deleted names
// ──────────────────────────────────────────────

test('can create a section definition with the same name as a soft-deleted one', function () {
    $def = SectionDefinition::create(['name' => 'A']);
    $def->delete();

    // This should NOT throw a unique constraint violation
    $newDef = SectionDefinition::create(['name' => 'A']);

    expect($newDef->exists)->toBeTrue();
    expect($newDef->id)->not->toBe($def->id);
    expect($newDef->trashed())->toBeFalse();
});

test('can create a grade definition with the same name as a soft-deleted one', function () {
    $def = GradeDefinition::create(['name' => '1er Año']);
    $def->delete();

    $newDef = GradeDefinition::create(['name' => '1er Año']);

    expect($newDef->exists)->toBeTrue();
    expect($newDef->id)->not->toBe($def->id);
});

test('can create a location with the same name as a soft-deleted one', function () {
    $loc = \App\Models\Location::create(['name' => 'Huerto Escolar']);
    $loc->delete();

    $newLoc = \App\Models\Location::create(['name' => 'Huerto Escolar']);

    expect($newLoc->exists)->toBeTrue();
});

test('can create an activity category with the same name as a soft-deleted one', function () {
    $cat = \App\Models\ActivityCategory::create(['name' => 'Siembra']);
    $cat->delete();

    $newCat = \App\Models\ActivityCategory::create(['name' => 'Siembra']);

    expect($newCat->exists)->toBeTrue();
});

test('can create a health condition with the same name as a soft-deleted one', function () {
    $hc = HealthCondition::create(['name' => 'Asma']);
    $hc->delete();

    $newHc = HealthCondition::create(['name' => 'Asma']);

    expect($newHc->exists)->toBeTrue();
});

test('can create an academic year with the same name as a soft-deleted one', function () {
    $year = AcademicYear::factory()->create(['name' => '2025-2026']);
    $year->delete();

    $newYear = AcademicYear::factory()->create(['name' => '2025-2026']);

    expect($newYear->exists)->toBeTrue();
});
