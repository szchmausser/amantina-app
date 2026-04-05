<?php

namespace Database\Seeders;

use App\Models\ActivityCategory;
use Illuminate\Database\Seeder;

class ActivityCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Desmalezamiento',
            'Limpieza de áreas verdes',
            'Siembra',
            'Riego',
            'Cosecha',
            'Mantenimiento de huerto',
            'Compostaje',
            'Recolección de residuos',
            'Construcción de cercas',
            'Pintura de infraestructura',
            'Reparación de mobiliario',
            'Mantenimiento de caminos',
            'Actividades de reforestación',
            'Otra actividad',
        ];

        foreach ($categories as $name) {
            ActivityCategory::create([
                'name' => $name,
            ]);
        }
    }
}
