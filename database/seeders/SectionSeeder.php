<?php

namespace Database\Seeders;

use App\Models\Grade;
use App\Models\Section;
use App\Models\SectionDefinition;
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

        $definitions = SectionDefinition::all();

        if ($definitions->isEmpty()) {
            $this->command->warn('No section definitions found. Please run SectionDefinitionSeeder first.');

            return;
        }

        // Take first 4 definitions (A-D) matching the original behavior
        $definitionNames = $definitions->pluck('name')->toArray();
        $selectedNames = array_slice($definitionNames, 0, 4);

        foreach ($grades as $grade) {
            // Randomly decide how many sections this grade will have (2 to 4)
            $sectionCount = min(fake()->numberBetween(2, 4), count($selectedNames));

            for ($i = 0; $i < $sectionCount; $i++) {
                $defName = $selectedNames[$i];
                $definition = $definitions->firstWhere('name', $defName);

                Section::updateOrCreate(
                    ['grade_id' => $grade->id, 'name' => $defName],
                    [
                        'academic_year_id' => $grade->academic_year_id,
                        'section_definition_id' => $definition?->id,
                        'section_definition_name' => $definition?->name,
                    ]
                );
            }
        }
    }
}
