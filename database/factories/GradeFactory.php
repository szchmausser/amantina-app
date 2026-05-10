<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\GradeDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Grade>
 */
class GradeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gradeDefinition = GradeDefinition::inRandomOrder()->first();

        return [
            'academic_year_id' => AcademicYear::factory(),
            'name' => $this->faker->unique()->word().' Año',
            'order' => $this->faker->unique()->numberBetween(1, 100),
            'grade_definition_id' => $gradeDefinition?->id,
            'grade_definition_name' => $gradeDefinition?->name,
        ];
    }
}
