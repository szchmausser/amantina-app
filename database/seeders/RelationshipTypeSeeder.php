<?php

namespace Database\Seeders;

use App\Models\RelationshipType;
use Illuminate\Database\Seeder;

class RelationshipTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['name' => 'Padre'],
            ['name' => 'Madre'],
            ['name' => 'Representante Legal'],
            ['name' => 'Abuelo/a'],
            ['name' => 'Tío/a'],
        ];

        foreach ($types as $type) {
            RelationshipType::updateOrCreate(['name' => $type['name']], $type);
        }
    }
}
