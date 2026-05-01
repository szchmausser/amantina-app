<?php

namespace Database\Seeders;

use App\Models\Grade;
use App\Models\Section;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates between 2 and 4 sections per grade with sequential naming (A, B, C, D).
     */
    public function run(): void
    {
        $grades = Grade::all();

        if ($grades->isEmpty()) {
            $this->command->warn('No grades found. Please run GradeSeeder first.');

            return;
        }

        $sectionNames = ['A', 'B', 'C', 'D'];

        foreach ($grades as $grade) {
            // Randomly decide how many sections this grade will have (2 to 4)
            $sectionCount = fake()->numberBetween(2, 4);

            for ($i = 0; $i < $sectionCount; $i++) {
                Section::updateOrCreate(
                    ['grade_id' => $grade->id, 'name' => $sectionNames[$i]],
                    ['academic_year_id' => $grade->academic_year_id]
                );
            }
        }
    }
}
