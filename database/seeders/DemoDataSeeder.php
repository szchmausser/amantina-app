<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Section;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $activeYear = AcademicYear::active()->with('grades.sections')->first();

        if (! $activeYear) {
            $this->command->error('No hay un año escolar activo. ¡Activa uno antes de correr este seeder!');

            return;
        }

        // Si no hay estructura, creamo una base para poder probar
        if ($activeYear->grades->isEmpty()) {
            $this->command->info('No hay grados configurados. Creando estructura básica de 5 grados con 3 secciones cada uno...');

            for ($i = 1; $i <= 5; $i++) {
                $grade = Grade::factory()->create([
                    'academic_year_id' => $activeYear->id,
                    'name' => "{$i}er Año",
                    'order' => $i,
                ]);

                foreach (['A', 'B', 'C'] as $sectionLetter) {
                    Section::factory()->create([
                        'academic_year_id' => $activeYear->id,
                        'grade_id' => $grade->id,
                        'name' => "Sección {$sectionLetter}",
                    ]);
                }
            }

            // Recargamos el año activo para que incluya los nuevos datos
            $activeYear->load('grades.sections');
        }

        $passwordHash = Hash::make('password');

        $this->command->info("Generando 500 alumnos aleatorios de prueba (password: 'password')...");
        $students = User::factory()->count(500)->create([
            'password' => $passwordHash,
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ]);

        foreach ($students as $student) {
            $student->assignRole('alumno');
        }

        $this->command->info("Generando 25 profesores aleatorios de prueba (password: 'password')...");
        $teachers = User::factory()->count(25)->create([
            'password' => $passwordHash,
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ]);

        foreach ($teachers as $teacher) {
            $teacher->assignRole('profesor');
        }

        $this->command->info('Distribuyendo alumnos (20 a 30 por sección) y profesores (1 a 3 por sección)...');

        $studentIndex = 0;

        foreach ($activeYear->grades as $grade) {
            foreach ($grade->sections as $section) {
                // Inscribir de 20 a 30 alumnos
                $numStudents = rand(20, 30);

                for ($i = 0; $i < $numStudents; $i++) {
                    if ($studentIndex >= count($students)) {
                        break;
                    }

                    Enrollment::firstOrCreate([
                        'user_id' => $students[$studentIndex]->id,
                        'academic_year_id' => $activeYear->id,
                    ], [
                        'grade_id' => $grade->id,
                        'section_id' => $section->id,
                    ]);

                    $studentIndex++;
                }

                // Asignar de 1 a 3 profesores por sección
                $numTeachers = rand(1, 3);
                $selectedTeachers = $teachers->random($numTeachers);

                foreach ($selectedTeachers as $teacher) {
                    TeacherAssignment::firstOrCreate([
                        'user_id' => $teacher->id,
                        'academic_year_id' => $activeYear->id,
                        'section_id' => $section->id,
                    ], [
                        'grade_id' => $grade->id,
                    ]);
                }
            }
        }

        $enrolledCount = Enrollment::where('academic_year_id', $activeYear->id)->count();
        $this->command->info("¡Semilla plantada! Se inscribieron {$enrolledCount} alumnos en el año activo.");

        if ($studentIndex < count($students)) {
            $pending = count($students) - $studentIndex;
            $this->command->warn("Quedaron {$pending} alumnos sin inscribir (útil para probar el formulario individual de Nuevo Ingreso).");
        }
    }
}
