<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Grade;
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
        return [
            'academic_year_id' => AcademicYear::factory(),
            'name' => $this->faker->unique()->word().' Año',
            'order' => $this->faker->unique()->numberBetween(1, 100),
        ];
    }
}
