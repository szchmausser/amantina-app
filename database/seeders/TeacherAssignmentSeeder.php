<?php

namespace Database\Seeders;

use App\Models\Section;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder to assign teachers to sections randomly.
 * Assigns between 0 and 2 teachers per section using existing users with 'profesor' role.
 */
class TeacherAssignmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teachers = User::role('profesor')->get();

        if ($teachers->isEmpty()) {
            $this->command->warn('No teachers found. Skipping teacher assignments.');

            return;
        }

        $sections = Section::all();

        foreach ($sections as $section) {
            // Determine how many teachers to assign (0 to 2)
            $assignCount = fake()->numberBetween(0, 2);

            if ($assignCount === 0) {
                continue;
            }

            // Pick random teachers from the collection
            // We use unique() on the slice to ensure we don't pick the same teacher twice in this loop
            $selectedTeachers = $teachers->shuffle()->take($assignCount)->unique('id');

            foreach ($selectedTeachers as $teacher) {
                // Use firstOrCreate to avoid unique constraint errors if seeder runs multiple times
                TeacherAssignment::firstOrCreate(
                    [
                        'academic_year_id' => $section->academic_year_id,
                        'section_id' => $section->id,
                        'user_id' => $teacher->id,
                    ],
                    [
                        'grade_id' => $section->grade_id,
                    ]
                );
            }
        }
    }
}
