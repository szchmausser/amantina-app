<?php

namespace Tests\Feature;

use App\Models\Grade;
use App\Models\Section;
use App\Models\TeacherAssignment;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicStructureSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the DatabaseSeeder creates the academic structure correctly.
     * Validates: 5 grades, 2-4 sections per grade, and teacher assignments.
     */
    public function test_academic_structure_and_teacher_assignments(): void
    {
        $this->seed(DatabaseSeeder::class);

        // 1. Verify Grades: Should be 5 grades
        $grades = Grade::all();
        $this->assertCount(5, $grades, 'Should have exactly 5 grades.');

        // 2. Verify Sections: Each grade should have between 2 and 4 sections
        foreach ($grades as $grade) {
            $sectionCount = $grade->sections()->count();
            $this->assertGreaterThanOrEqual(2, $sectionCount, "Grade {$grade->name} should have at least 2 sections.");
            $this->assertLessThanOrEqual(4, $sectionCount, "Grade {$grade->name} should have at most 4 sections.");
        }

        // 3. Verify Teacher Assignments: Total sections and assignments
        $sections = Section::all();
        $totalSections = $sections->count();

        // Total sections should be between 5*2=10 and 5*4=20
        $this->assertGreaterThanOrEqual(10, $totalSections, 'Total sections should be at least 10.');
        $this->assertLessThanOrEqual(20, $totalSections, 'Total sections should be at most 20.');

        // Check teacher assignments per section (0 to 2)
        foreach ($sections as $section) {
            $teacherCount = TeacherAssignment::where('section_id', $section->id)->count();
            $this->assertGreaterThanOrEqual(0, $teacherCount, 'Section should have at least 0 teachers.');
            $this->assertLessThanOrEqual(2, $teacherCount, 'Section should have at most 2 teachers.');
        }

        // 4. Verify no duplicate teacher assignments for the same section and academic year
        $assignments = TeacherAssignment::all();
        foreach ($assignments as $assignment) {
            $duplicates = TeacherAssignment::where('academic_year_id', $assignment->academic_year_id)
                ->where('section_id', $assignment->section_id)
                ->where('user_id', $assignment->user_id)
                ->count();
            $this->assertEquals(1, $duplicates, 'Teacher assignment should be unique per section and academic year.');
        }
    }
}
