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
            AcademicYearSeeder::class,    // 1. Creates the Academic Year
            SchoolTermSeeder::class,      // 2. Creates Terms (Lapsos) for the active year
            GradeSeeder::class,           // 3. Creates Grades for the active year
            SectionSeeder::class,         // 4. Creates 2-4 sequential sections per grade
            TeacherAssignmentSeeder::class, // 5. Assigns 0-2 teachers to sections
            ActivityCategorySeeder::class,
            LocationSeeder::class,
            HealthConditionSeeder::class,
            FieldSessionStatusSeeder::class,
        ]);
    }
}
