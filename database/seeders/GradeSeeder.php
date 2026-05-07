<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\GradeDefinition;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // We assume an active Academic Year exists (created by AcademicYearSeeder)
        $year = AcademicYear::where('is_active', true)->first();

        if (! $year) {
            $this->command->warn('No active Academic Year found. Please run AcademicYearSeeder first.');

            return;
        }

        $definitions = GradeDefinition::orderBy('order')->get();

        if ($definitions->isEmpty()) {
            $this->command->warn('No grade definitions found. Please run GradeDefinitionSeeder first.');

            return;
        }

        foreach ($definitions as $definition) {
            Grade::updateOrCreate(
                [
                    'academic_year_id' => $year->id,
                    'name' => $definition->name,
                ],
                [
                    'order' => $definition->order,
                    'grade_definition_id' => $definition->id,
                    'grade_definition_name' => $definition->name,
                ]
            );
        }
    }
}
