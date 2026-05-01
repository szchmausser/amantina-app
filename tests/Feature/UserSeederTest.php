<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the DatabaseSeeder creates the correct number of users
     * with the correct role distribution.
     */
    public function test_database_seeder_creates_correct_user_distribution(): void
    {
        $this->seed(DatabaseSeeder::class);

        // Total users: 1 (admin) + 99 (test users) = 100
        $this->assertCount(100, User::all());

        // Verify Admin count (created in UserSeeder)
        $this->assertCount(1, User::role('admin')->get());

        // Verify Profesor count (5)
        $this->assertCount(5, User::role('profesor')->get());

        // Verify Representante count (9)
        $this->assertCount(9, User::role('representante')->get());

        // Verify Alumno count (85)
        $this->assertCount(85, User::role('alumno')->get());
    }
}
