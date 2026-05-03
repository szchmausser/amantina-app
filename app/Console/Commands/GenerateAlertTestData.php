<?php

namespace App\Console\Commands;

use App\Models\AcademicYear;
use App\Models\ActivityCategory;
use App\Models\Attendance;
use App\Models\AttendanceActivity;
use App\Models\Enrollment;
use App\Models\FieldSession;
use App\Models\FieldSessionStatus;
use App\Models\Location;
use App\Models\SchoolTerm;
use App\Models\User;
use Illuminate\Console\Command;

class GenerateAlertTestData extends Command
{
    protected $signature = 'test:generate-alerts';

    protected $description = 'Generate test data to trigger dashboard alerts';

    public function handle(): int
    {
        $this->info('Generando datos de prueba para alertas del dashboard...');

        $activeYear = AcademicYear::active()->first();
        if (! $activeYear) {
            $this->error('No hay un año escolar activo.');

            return 1;
        }

        $realizedStatus = FieldSessionStatus::where('name', 'realized')->first();
        if (! $realizedStatus) {
            $this->error('No existe el estado "realized".');

            return 1;
        }

        $term = SchoolTerm::where('academic_year_id', $activeYear->id)->first();
        $location = Location::first();
        $teacher = User::role('profesor')->first();
        $categories = ActivityCategory::all();

        if (! $term || ! $location || ! $teacher || $categories->isEmpty()) {
            $this->error('Faltan datos base (términos, ubicaciones, profesores o categorías).');

            return 1;
        }

        // 1. Crear jornada SIN asistencia
        $this->info('1. Creando jornada realizada sin asistencia...');
        $sessionNoAttendance = FieldSession::create([
            'name' => '[TEST] Jornada sin Asistencia',
            'academic_year_id' => $activeYear->id,
            'school_term_id' => $term->id,
            'user_id' => $teacher->id,
            'status_id' => $realizedStatus->id,
            'start_datetime' => now()->subDays(5),
            'end_datetime' => now()->subDays(5)->addHours(4),
            'location_name' => $location->name,
            'base_hours' => 4,
            'description' => 'Jornada de prueba sin registro de asistencia',
        ]);
        $this->info("   ✓ Jornada creada: {$sessionNoAttendance->name} (ID: {$sessionNoAttendance->id})");

        // 2. Crear jornada CON asistencia pero SIN actividades
        $this->info('2. Creando jornada con asistencia pero sin actividades...');
        $sessionNoActivities = FieldSession::create([
            'name' => '[TEST] Jornada sin Actividades',
            'academic_year_id' => $activeYear->id,
            'school_term_id' => $term->id,
            'user_id' => $teacher->id,
            'status_id' => $realizedStatus->id,
            'start_datetime' => now()->subDays(3),
            'end_datetime' => now()->subDays(3)->addHours(4),
            'location_name' => $location->name,
            'base_hours' => 4,
            'description' => 'Jornada de prueba con asistencia pero sin actividades',
        ]);

        // Crear 3 asistencias sin actividades
        $students = Enrollment::where('academic_year_id', $activeYear->id)
            ->with('student')
            ->limit(3)
            ->get();

        foreach ($students as $enrollment) {
            Attendance::create([
                'field_session_id' => $sessionNoActivities->id,
                'user_id' => $enrollment->student->id,
                'academic_year_id' => $activeYear->id,
                'attended' => true,
            ]);
        }
        $this->info("   ✓ Jornada creada: {$sessionNoActivities->name} (ID: {$sessionNoActivities->id})");
        $this->info("   ✓ 3 asistencias creadas sin actividades");

        // 3. Crear jornada CON asistencia y actividades pero con 0 horas
        $this->info('3. Creando asistencias con 0 horas...');
        $sessionZeroHours = FieldSession::create([
            'name' => '[TEST] Jornada con 0 Horas',
            'academic_year_id' => $activeYear->id,
            'school_term_id' => $term->id,
            'user_id' => $teacher->id,
            'status_id' => $realizedStatus->id,
            'start_datetime' => now()->subDays(1),
            'end_datetime' => now()->subDays(1)->addHours(4),
            'location_name' => $location->name,
            'base_hours' => 4,
            'description' => 'Jornada de prueba con actividades de 0 horas',
        ]);

        // Crear 2 asistencias con actividades de 0 horas
        $moreStudents = Enrollment::where('academic_year_id', $activeYear->id)
            ->with('student')
            ->skip(3)
            ->limit(2)
            ->get();

        foreach ($moreStudents as $enrollment) {
            $attendance = Attendance::create([
                'field_session_id' => $sessionZeroHours->id,
                'user_id' => $enrollment->student->id,
                'academic_year_id' => $activeYear->id,
                'attended' => true,
            ]);

            // Crear actividad con 0 horas
            AttendanceActivity::create([
                'attendance_id' => $attendance->id,
                'activity_category_id' => $categories->first()->id,
                'hours' => 0,
                'notes' => 'Actividad de prueba con 0 horas',
            ]);
        }
        $this->info("   ✓ Jornada creada: {$sessionZeroHours->name} (ID: {$sessionZeroHours->id})");
        $this->info("   ✓ 2 asistencias creadas con actividades de 0 horas");

        $this->newLine();
        $this->info('✅ Datos de prueba generados exitosamente!');
        $this->info('');
        $this->info('Ahora deberías ver 3 alertas en el dashboard:');
        $this->info('  1. 1 jornada realizada sin registro de asistencia');
        $this->info('  2. 1 jornada con asistencia pero sin actividades (3 asistencias)');
        $this->info('  3. 2 asistencias marcadas como presentes con 0 horas');
        $this->info('');
        $this->info('Para limpiar estos datos de prueba, ejecuta:');
        $this->info('  php artisan test:clean-alerts');

        return 0;
    }
}
