<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            InstitutionSeeder::class,
            RelationshipTypeSeeder::class,
            RoleAndPermissionSeeder::class,
            UserSeeder::class,
            TestUsersSeeder::class,
            AcademicYearSeeder::class,
            ActivityCategorySeeder::class,
            LocationSeeder::class,
            HealthConditionSeeder::class,
        ]);
    }
}
