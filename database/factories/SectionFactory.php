<?php

namespace Database\Factories;

use App\Models\Grade;
use App\Models\Section;
use App\Models\SectionDefinition;
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
        $letter = $this->faker->unique()->randomElement(['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H']);
        $sectionDefinition = SectionDefinition::inRandomOrder()->first();

        return [
            'grade_id' => Grade::factory(),
            'academic_year_id' => function (array $attributes) {
                return Grade::find($attributes['grade_id'])->academic_year_id;
            },
            'name' => "Sección {$letter}",
            'section_definition_id' => $sectionDefinition?->id,
            'section_definition_name' => $sectionDefinition?->name,
        ];
    }
}
