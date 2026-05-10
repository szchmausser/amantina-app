<?php

namespace Database\Factories;

use App\Models\SectionDefinition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SectionDefinition>
 */
class SectionDefinitionFactory extends Factory
{
    protected $model = SectionDefinition::class;

    public function definition(): array
    {
        return [
            'name' => strtoupper(fake()->unique()->randomLetter()),
            'is_active' => true,
        ];
    }
}
