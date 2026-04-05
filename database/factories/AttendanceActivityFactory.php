<?php

namespace Database\Factories;

use App\Models\ActivityCategory;
use App\Models\Attendance;
use App\Models\AttendanceActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceActivity>
 */
class AttendanceActivityFactory extends Factory
{
    protected $model = AttendanceActivity::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $activityCategory = ActivityCategory::first() ?? ActivityCategory::factory()->create();

        return [
            'attendance_id' => Attendance::factory(),
            'activity_category_id' => $activityCategory->id,
            'hours' => fake()->randomFloat(2, 0.5, 4),
            'notes' => null,
        ];
    }

    public function withNotes(): static
    {
        return $this->state(fn (array $attributes) => [
            'notes' => fake()->optional()->sentence(),
        ]);
    }
}
