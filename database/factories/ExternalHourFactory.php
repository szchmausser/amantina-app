<?php

namespace Database\Factories;

use App\Models\ExternalHour;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExternalHour>
 */
class ExternalHourFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $schoolNames = [
            'U.E. Liceo Bolivariano Simón Rodríguez',
            'U.E. Colegio La Salle',
            'U.E. Colegio Don Bosco',
            'U.E. Instituto San José',
            'U.E. Colegio Nuestra Señora del Carmen',
            'U.E. Escuela Técnica Industrial Rafael Urdaneta',
            'U.E. Liceo Nacional Fermín Toro',
            'U.E. Colegio San Agustín',
        ];

        $startYear = fake()->numberBetween(2015, 2022);
        $endYear = fake()->numberBetween($startYear + 1, $startYear + 4);

        return [
            'user_id' => User::factory(),
            'admin_id' => User::factory(),
            'period' => "{$startYear}-{$endYear}",
            'hours' => fake()->randomFloat(2, 10, 200),
            'institution_name' => fake()->randomElement($schoolNames),
            'description' => fake()->optional(0.7)->sentence(),
        ];
    }
}
