<?php

namespace Database\Factories;

use App\Models\HealthCondition;
use App\Models\StudentHealthRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentHealthRecord>
 */
class StudentHealthRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'health_condition_id' => HealthCondition::factory(),
            'received_by' => User::factory(),
            'received_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'received_at_location' => fake()->optional()->city(),
            'observations' => fake()->optional()->paragraph(),
        ];
    }
}
