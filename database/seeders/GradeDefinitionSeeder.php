<?php

namespace Database\Seeders;

use App\Models\GradeDefinition;
use Illuminate\Database\Seeder;

class GradeDefinitionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $definitions = [
            ['name' => '1er Año', 'order' => 1],
            ['name' => '2do Año', 'order' => 2],
            ['name' => '3er Año', 'order' => 3],
            ['name' => '4to Año', 'order' => 4],
            ['name' => '5to Año', 'order' => 5],
        ];

        foreach ($definitions as $definition) {
            GradeDefinition::updateOrCreate(
                ['name' => $definition['name']],
                ['order' => $definition['order'], 'is_active' => true]
            );
        }
    }
}
