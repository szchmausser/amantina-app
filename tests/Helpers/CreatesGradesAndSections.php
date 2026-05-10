<?php

namespace Tests\Helpers;

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\GradeDefinition;
use App\Models\Section;
use App\Models\SectionDefinition;

/**
 * Helper trait for creating grades and sections with the new schema.
 *
 * After the grade-section-definitions feature, grades and sections require
 * grade_definition_id and section_definition_id instead of free-text names.
 * This trait provides helper methods to create test data using the new schema.
 */
trait CreatesGradesAndSections
{
    /**
     * Create a grade with a grade definition.
     *
     * This method first creates or finds a GradeDefinition, then creates a Grade
     * using that definition's ID. This matches the new schema requirements.
     *
     * @param  AcademicYear  $academicYear  The academic year for the grade
     * @param  string  $name  The name of the grade definition (e.g., '1er Año')
     * @param  int  $order  The display order of the grade
     * @return Grade The created grade instance
     */
    protected function createGradeWithDefinition(
        AcademicYear $academicYear,
        string $name = '1er Año',
        int $order = 1
    ): Grade {
        $definition = GradeDefinition::firstOrCreate(['name' => $name]);

        return Grade::create([
            'grade_definition_id' => $definition->id,
            'grade_definition_name' => $definition->name,
            'name' => $definition->name,
            'academic_year_id' => $academicYear->id,
            'order' => $order,
        ]);
    }

    /**
     * Create a section with a section definition.
     *
     * This method first creates or finds a SectionDefinition, then creates a Section
     * using that definition's ID. This matches the new schema requirements.
     *
     * @param  Grade  $grade  The grade for the section
     * @param  string  $name  The name of the section definition (e.g., 'A')
     * @return Section The created section instance
     */
    protected function createSectionWithDefinition(
        Grade $grade,
        string $name = 'A'
    ): Section {
        $definition = SectionDefinition::firstOrCreate(['name' => $name]);

        return Section::create([
            'section_definition_id' => $definition->id,
            'section_definition_name' => $definition->name,
            'name' => $definition->name,
            'grade_id' => $grade->id,
            'academic_year_id' => $grade->academic_year_id,
        ]);
    }
}
