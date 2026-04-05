<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            'Huerto escolar',
            'Cancha deportiva',
            'Jardines de la institución',
            'Áreas verdes del plantel',
            'Comunidad El Palmar',
            'Finca experimental',
            'Vivero institucional',
            'Campo deportivo municipal',
            'Plaza Bolívar',
            'Otra ubicación',
        ];

        foreach ($locations as $name) {
            Location::create([
                'name' => $name,
            ]);
        }
    }
}
