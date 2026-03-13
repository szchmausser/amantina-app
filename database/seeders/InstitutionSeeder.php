<?php

namespace Database\Seeders;

use App\Models\Institution;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Institution::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'Amantina de Sucre',
                'address' => 'Dirección de la institución',
                'email' => 'contacto@amantina.edu',
                'phone' => '04120000000',
                'code' => 'AM-001',
            ]
        );
    }
}
