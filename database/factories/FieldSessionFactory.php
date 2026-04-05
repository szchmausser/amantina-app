<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\FieldSession;
use App\Models\FieldSessionStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FieldSession>
 */
class FieldSessionFactory extends Factory
{
    protected $model = FieldSession::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $academicYear = AcademicYear::first() ?? AcademicYear::factory()->create();
        $teacher = User::role('profesor')->first() ?? User::factory()->create();
        $status = FieldSessionStatus::first() ?? FieldSessionStatus::factory()->create();

        $startDatetime = fake()->dateTimeBetween('-1 month', '+1 month');
        $endDatetime = (clone $startDatetime)->modify('+'.fake()->numberBetween(1, 4).' hours');
        $baseHours = round(($endDatetime->getTimestamp() - $startDatetime->getTimestamp()) / 3600, 2);

        return [
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'academic_year_id' => $academicYear->id,
            'school_term_id' => null,
            'user_id' => $teacher->id,
            'activity_name' => fake()->randomElement(['Siembra', 'Limpieza', 'Riego', 'Cosecha']),
            'location_name' => fake()->randomElement(['Huerto escolar', 'Cancha', 'Comunidad']),
            'start_datetime' => $startDatetime,
            'end_datetime' => $endDatetime,
            'base_hours' => $baseHours,
            'status_id' => $status->id,
            'cancellation_reason' => null,
        ];
    }

    public function planned(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_datetime' => fake()->dateTimeBetween('+1 week', '+1 month'),
            'end_datetime' => fake()->dateTimeBetween('+1 week +2 hours', '+1 month +4 hours'),
        ]);
    }

    public function realized(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_datetime' => fake()->dateTimeBetween('-1 month', '-1 week'),
            'end_datetime' => fake()->dateTimeBetween('-1 week', '-1 day'),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'cancellation_reason' => fake()->sentence(),
        ]);
    }
}
