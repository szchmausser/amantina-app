<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'cedula' => '00000000',
            'name' => 'Administrador',
            'email' => 'admin@amantina.test',
            'password' => 'password',
            'phone' => '04121234567',
            'address' => null,
            'is_active' => true,
            'is_transfer' => false,
            'institution_origin' => null,
        ])->assignRole('admin');
    }
}
