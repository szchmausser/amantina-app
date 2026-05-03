<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\SchoolTerm;
use App\Models\TermType;
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
        $termType = TermType::first() ?? TermType::create(['name' => 'Lapso 1', 'order' => 1]);

        return [
            'academic_year_id' => AcademicYear::factory(),
            'term_type_id' => $termType->id,
            'term_type_name' => $termType->name,
            'start_date' => fake()->dateTimeBetween('-2 years', '-1 year')->format('Y-m-d'),
            'end_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
        ];
    }
}
