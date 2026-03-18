<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder to generate a set of demo users with different roles.
 */
class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /** @var array<int, string> $roles */
        $roles = [
            'admin',
            'profesor',
            'alumno',
            'representante',
        ];

        // Create 20 demo users, rotating through available roles.
        for ($i = 1; $i <= 20; $i++) {
            /** @var User $user */
            $user = User::factory()->create([
                'cedula' => sprintf('9000%04d', $i),
                'name' => "Usuario demo {$i}",
                'email' => "demo{$i}@amantina.test",
                'is_active' => true,
            ]);

            $roleName = $roles[($i - 1) % \count($roles)];
            $user->assignRole($roleName);
        }
    }
}
