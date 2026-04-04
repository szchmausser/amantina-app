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
        $year = AcademicYear::factory()->create();
        $termType = TermType::first() ?? TermType::create(['name' => 'Lapso 1', 'order' => 1]);

        return [
            'academic_year_id' => $year->id,
            'term_type_id' => $termType->id,
            'start_date' => $year->start_date->format('Y-m-d'),
            'end_date' => $year->start_date->copy()->addMonths(3)->format('Y-m-d'),
        ];
    }
}
