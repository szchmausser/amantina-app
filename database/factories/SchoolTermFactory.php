<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\SchoolTerm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SchoolTerm>
 */
class SchoolTermFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = AcademicYear::factory()->create();

        return [
            'academic_year_id' => $year->id,
            'term_number' => 1,
            'start_date' => $year->start_date->format('Y-m-d'),
            'end_date' => $year->start_date->copy()->addMonths(3)->format('Y-m-d'),
        ];
    }
}
