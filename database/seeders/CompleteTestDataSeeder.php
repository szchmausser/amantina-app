<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CompleteTestDataSeeder extends Seeder
{
    /**
     * Seed the application's database with complete test data.
     *
     * This seeder creates a full realistic scenario with:
     * - Institution and base configuration
     * - Users (admin, teachers, students)
     * - Academic structure (years, terms, grades, sections)
     * - Enrollments and teacher assignments
     * - Field sessions with activities and attendances
     */
    public function run(): void
    {
        $this->command->info('🌱 Iniciando semilla completa de datos de prueba...');
        $this->command->newLine();

        // 1. Base configuration
        $this->command->info('📋 Paso 1/8: Configuración base');
        $this->call([
            InstitutionSeeder::class,
            RelationshipTypeSeeder::class,
            RoleAndPermissionSeeder::class,
            FieldSessionStatusSeeder::class,
        ]);
        $this->command->newLine();

        // 2. Users
        $this->command->info('👥 Paso 2/8: Usuarios del sistema');
        $this->call([
            UserSeeder::class,
            TestUsersSeeder::class,
        ]);
        $this->command->newLine();

        // 3. Academic structure
        $this->command->info('🏫 Paso 3/8: Estructura académica');
        $this->call([
            AcademicYearSeeder::class,
            SchoolTermSeeder::class,
            GradeSeeder::class,
            SectionSeeder::class,
        ]);
        $this->command->newLine();

        // 4. Catalogs
        $this->command->info('📚 Paso 4/8: Catálogos');
        $this->call([
            ActivityCategorySeeder::class,
            LocationSeeder::class,
            HealthConditionSeeder::class,
        ]);
        $this->command->newLine();

        // 5. Students and teachers
        $this->command->info('🎓 Paso 5/8: Estudiantes y profesores');
        $this->call([
            DemoDataSeeder::class,
        ]);
        $this->command->newLine();

        // 6. Field sessions with activities
        $this->command->info('📅 Paso 6/8: Jornadas de campo');
        $this->call([
            FieldSessionsSeeder::class,
        ]);
        $this->command->newLine();

        $this->command->info('✅ ¡Semilla completa de datos de prueba finalizada!');
        $this->command->newLine();
        $this->command->info('📊 Puedes acceder al sistema con:');
        $this->command->info('   Admin: admin@example.com / password');
        $this->command->info('   Profesor: profesor@example.com / password');
        $this->command->info('   Alumno: alumno@example.com / password');
    }
}
