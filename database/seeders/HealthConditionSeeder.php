<?php

namespace Database\Seeders;

use App\Models\HealthCondition;
use Illuminate\Database\Seeder;

class HealthConditionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $conditions = [
            'Asma',
            'Diabetes tipo 1',
            'Diabetes tipo 2',
            'Epilepsia',
            'Alergia alimentaria',
            'Alergia a medicamentos',
            'Alergia a picaduras de insectos',
            'Problemas cardíacos',
            'Hipertensión arterial',
            'Anemia',
            'Trastornos de coagulación',
            'Problemas de visión',
            'Problemas de audición',
            'Trastorno del espectro autista',
            'TDAH (Déficit de atención e hiperactividad)',
            'Asma inducida por ejercicio',
            'Intolerancia a la lactosa',
            'Celiaquía',
            'Otra condición',
        ];

        foreach ($conditions as $name) {
            HealthCondition::create([
                'name' => $name,
                'is_active' => true,
            ]);
        }
    }
}
