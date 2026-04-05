<?php

namespace Database\Seeders;

use App\Models\FieldSessionStatus;
use Illuminate\Database\Seeder;

class FieldSessionStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['name' => 'planned', 'description' => 'Jornada planificada (aún no se realiza)'],
            ['name' => 'realized', 'description' => 'Jornada realizada (ya se ejecutó)'],
            ['name' => 'cancelled', 'description' => 'Jornada cancelada (no se realizó)'],
        ];

        foreach ($statuses as $status) {
            FieldSessionStatus::updateOrCreate(
                ['name' => $status['name']],
                $status,
            );
        }
    }
}
