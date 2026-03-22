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
        $year = AcademicYear::factory()->create();
        $order = 1;

        return [
            'academic_year_id' => $year->id,
            'name' => '1er Año',
            'order' => $order,
        ];
    }
}
