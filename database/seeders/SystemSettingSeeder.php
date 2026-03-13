<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            'institution_name' => 'Amantina de Sucre',
        ];

        foreach ($settings as $key => $value) {
            SystemSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
