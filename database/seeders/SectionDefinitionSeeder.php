<?php

namespace Database\Seeders;

use App\Models\SectionDefinition;
use Illuminate\Database\Seeder;

class SectionDefinitionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $definitions = ['A', 'B', 'C', 'D', 'E'];

        foreach ($definitions as $name) {
            SectionDefinition::updateOrCreate(
                ['name' => $name],
                ['is_active' => true]
            );
        }
    }
}
