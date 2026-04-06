<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\FieldSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $academicYear = AcademicYear::first() ?? AcademicYear::factory()->create();
        $fieldSession = FieldSession::first() ?? FieldSession::factory()->create();

        return [
            'field_session_id' => $fieldSession->id,
            'user_id' => User::role('alumno')->first() ?? User::factory()->create(),
            'academic_year_id' => $academicYear->id,
            'attended' => true,
            'notes' => null,
        ];
    }

    public function absent(): static
    {
        return $this->state(fn (array $attributes) => [
            'attended' => false,
            'notes' => fake()->optional()->sentence(),
        ]);
    }

    public function withNotes(): static
    {
        return $this->state(fn (array $attributes) => [
            'notes' => fake()->sentence(),
        ]);
    }
}
