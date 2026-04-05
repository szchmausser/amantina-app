<?php

namespace Database\Factories;

use App\Models\FieldSessionStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FieldSessionStatus>
 */
class FieldSessionStatusFactory extends Factory
{
    protected $model = FieldSessionStatus::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
