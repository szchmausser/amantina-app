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

        // Total users: 1 (admin from UserSeeder) + 17 (TestUsersSeeder: 5 prof + 2 rep + 10 alum) = 18
        $this->assertCount(18, User::all());

        // Verify Admin count (created in UserSeeder)
        $this->assertCount(1, User::role('admin')->get());

        // Verify Profesor count (5 from TestUsersSeeder)
        $this->assertCount(5, User::role('profesor')->get());

        // Verify Representante count (2 from TestUsersSeeder)
        $this->assertCount(2, User::role('representante')->get());

        // Verify Alumno count (10 from TestUsersSeeder)
        $this->assertCount(10, User::role('alumno')->get());
    }
}
