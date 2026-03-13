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
            'is_active' => true,
        ]);
    }
}
