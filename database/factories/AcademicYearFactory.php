<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startYear = fake()->unique()->numberBetween(2020, 2030);

        return [
            'name' => "{$startYear}-".($startYear + 1),
            'start_date' => "{$startYear}-09-01",
            'end_date' => ($startYear + 1).'-07-15',
            'is_active' => false,
            'required_hours' => 600,
        ];
    }
}
