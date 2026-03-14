<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create base roles for the system
        $roles = [
            'admin',
            'profesor',
            'alumno',
            'representante',
        ];

        foreach ($roles as $roleName) {
            Role::findOrCreate($roleName);
        }
    }
}
