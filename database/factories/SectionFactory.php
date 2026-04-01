<?php

namespace Database\Factories;

use App\Models\Grade;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Section>
 */
class SectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'grade_id' => Grade::factory(),
            'academic_year_id' => function (array $attributes) {
                return Grade::find($attributes['grade_id'])->academic_year_id;
            },
            'name' => $this->faker->unique()->lexify('Section ???'),
        ];
    }
}
