<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'admin@amantina.test'], // Search by unique email
            [
                'cedula' => '00000000',
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'phone' => '04121234567',
                'address' => null,
                'is_active' => true,
                'is_transfer' => false,
                'institution_origin' => null,
            ]
        );

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $user->assignRole(Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]));
    }
}
