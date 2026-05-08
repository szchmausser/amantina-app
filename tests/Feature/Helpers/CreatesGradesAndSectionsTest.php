<?php

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\GradeDefinition;
use App\Models\Section;
use App\Models\SectionDefinition;
use Tests\Helpers\CreatesGradesAndSections;

uses(CreatesGradesAndSections::class);

test('createGradeWithDefinition creates a grade with a grade definition', function () {
    $academicYear = AcademicYear::factory()->create();

    $grade = $this->createGradeWithDefinition($academicYear, '1er Año', 1);

    expect($grade)->toBeInstanceOf(Grade::class)
        ->and($grade->academic_year_id)->toBe($academicYear->id)
        ->and($grade->order)->toBe(1)
        ->and($grade->grade_definition_id)->not->toBeNull()
        ->and($grade->gradeDefinition)->toBeInstanceOf(GradeDefinition::class)
        ->and($grade->gradeDefinition->name)->toBe('1er Año');
});

test('createGradeWithDefinition reuses existing grade definitions', function () {
    $academicYear1 = AcademicYear::factory()->create();
    $academicYear2 = AcademicYear::factory()->create();

    // Create first grade with definition in first academic year
    $grade1 = $this->createGradeWithDefinition($academicYear1, '1er Año', 1);

    // Create second grade with same definition name in different academic year
    $grade2 = $this->createGradeWithDefinition($academicYear2, '1er Año', 1);

    // Both grades should use the same definition
    expect($grade1->grade_definition_id)->toBe($grade2->grade_definition_id)
        ->and(GradeDefinition::where('name', '1er Año')->count())->toBe(1);
});

test('createSectionWithDefinition creates a section with a section definition', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade = $this->createGradeWithDefinition($academicYear, '1er Año', 1);

    $section = $this->createSectionWithDefinition($grade, 'A');

    expect($section)->toBeInstanceOf(Section::class)
        ->and($section->grade_id)->toBe($grade->id)
        ->and($section->academic_year_id)->toBe($academicYear->id)
        ->and($section->section_definition_id)->not->toBeNull()
        ->and($section->sectionDefinition)->toBeInstanceOf(SectionDefinition::class)
        ->and($section->sectionDefinition->name)->toBe('A');
});

test('createSectionWithDefinition reuses existing section definitions', function () {
    $academicYear = AcademicYear::factory()->create();
    $grade1 = $this->createGradeWithDefinition($academicYear, '1er Año', 1);
    $grade2 = $this->createGradeWithDefinition($academicYear, '2do Año', 2);

    // Create first section with definition in first grade
    $section1 = $this->createSectionWithDefinition($grade1, 'A');

    // Create second section with same definition name in different grade
    $section2 = $this->createSectionWithDefinition($grade2, 'A');

    // Both sections should use the same definition
    expect($section1->section_definition_id)->toBe($section2->section_definition_id)
        ->and(SectionDefinition::where('name', 'A')->count())->toBe(1);
});

test('helper methods work with custom names', function () {
    $academicYear = AcademicYear::factory()->create();

    $grade = $this->createGradeWithDefinition($academicYear, '5to Año', 5);
    $section = $this->createSectionWithDefinition($grade, 'Z');

    expect($grade->gradeDefinition->name)->toBe('5to Año')
        ->and($grade->order)->toBe(5)
        ->and($section->sectionDefinition->name)->toBe('Z');
});
