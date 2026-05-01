<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder to generate users with specific role distribution.
 * Creates 99 users: 5 teachers, 9 representatives, 85 students.
 * Combined with UserSeeder (1 admin), total = 100 users.
 */
class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createUsersByRole('profesor', 5, 90000001);
        $this->createUsersByRole('representante', 9, 90000010);
        $this->createUsersByRole('alumno', 85, 90000020);
    }

    /**
     * Create multiple users with a specific role.
     */
    private function createUsersByRole(string $role, int $count, int $startCedula): void
    {
        for ($i = 0; $i < $count; $i++) {
            $cedula = (string) ($startCedula + $i);
            $email = "user{$cedula}@amantina.test";

            /** @var User $user */
            $user = User::updateOrCreate(
                ['cedula' => $cedula],
                [
                    'name' => "Usuario {$cedula}",
                    'email' => $email,
                    'password' => Hash::make('password'),
                    'phone' => '04120000000',
                    'address' => 'Dirección de prueba',
                    'is_active' => true,
                    'is_transfer' => false,
                    'institution_origin' => null,
                ]
            );

            $user->assignRole($role);
        }
    }
}
