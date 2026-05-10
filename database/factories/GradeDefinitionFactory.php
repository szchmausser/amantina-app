<?php

namespace Database\Factories;

use App\Models\GradeDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GradeDefinition>
 */
class GradeDefinitionFactory extends Factory
{
    protected $model = GradeDefinition::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word().' Año',
            'order' => fake()->numberBetween(1, 5),
            'is_active' => true,
        ];
    }
}
