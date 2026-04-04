<?php

namespace Database\Seeders;

use App\Models\TermType;
use Illuminate\Database\Seeder;

class TermTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            ['name' => 'Lapso 1', 'order' => 1],
            ['name' => 'Lapso 2', 'order' => 2],
            ['name' => 'Lapso 3', 'order' => 3],
        ];

        foreach ($types as $type) {
            TermType::updateOrCreate(['name' => $type['name']], $type);
        }
    }
}
