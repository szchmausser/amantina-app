<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\ActivityCategory;
use App\Models\Attendance;
use App\Models\AttendanceActivity;
use App\Models\Enrollment;
use App\Models\FieldSession;
use App\Models\FieldSessionStatus;
use App\Models\Location;
use App\Models\SchoolTerm;
use App\Models\Section;
use App\Models\TeacherAssignment;
use Illuminate\Database\Seeder;

class FieldSessionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $activeYear = AcademicYear::active()->first();

        if (! $activeYear) {
            $this->command->error('No hay un año escolar activo. ¡Activa uno antes de correr este seeder!');

            return;
        }

        // Verificar que existan categorías de actividades
        $categories = ActivityCategory::all();
        if ($categories->isEmpty()) {
            $this->command->error('No hay categorías de actividades. Ejecuta ActivityCategorySeeder primero.');

            return;
        }

        // Verificar que existan ubicaciones
        $locations = Location::all();
        if ($locations->isEmpty()) {
            $this->command->error('No hay ubicaciones. Ejecuta LocationSeeder primero.');

            return;
        }

        // Obtener estados de sesión
        $completedStatus = FieldSessionStatus::where('name', 'realized')->first();
        $cancelledStatus = FieldSessionStatus::where('name', 'cancelled')->first();

        if (! $completedStatus || ! $cancelledStatus) {
            $this->command->error('No hay estados de sesión. Ejecuta FieldSessionStatusSeeder primero.');

            return;
        }

        // Obtener términos escolares
        $terms = SchoolTerm::where('academic_year_id', $activeYear->id)->get();
        if ($terms->isEmpty()) {
            $this->command->error('No hay términos escolares. Ejecuta SchoolTermSeeder primero.');

            return;
        }

        $this->command->info('Generando jornadas de campo con actividades y asistencias...');

        $totalSessions = 0;
        $totalAttendances = 0;
        $totalActivities = 0;

        // Obtener todos los profesores
        $teachers = TeacherAssignment::with('teacher')
            ->where('academic_year_id', $activeYear->id)
            ->get()
            ->pluck('teacher')
            ->unique('id');

        if ($teachers->isEmpty()) {
            $this->command->error('No hay profesores asignados.');

            return;
        }

        // Generar entre 120 y 180 jornadas totales (más jornadas para simular año cerca de terminar)
        $numSessions = rand(120, 180);

        for ($i = 0; $i < $numSessions; $i++) {
            // Seleccionar un término aleatorio
            $term = $terms->random();

            // Seleccionar un profesor aleatorio
            $teacher = $teachers->random();

            // Seleccionar una ubicación aleatoria
            $location = $locations->random();

            // Generar fecha aleatoria dentro del término
            $startDate = \Carbon\Carbon::parse($term->start_date);
            $endDate = \Carbon\Carbon::parse($term->end_date);
            $sessionDate = $startDate->copy()->addDays(rand(0, max(0, $startDate->diffInDays($endDate))));

            // Hora de inicio entre 7am y 2pm
            $startHour = rand(7, 14);
            $startDateTime = $sessionDate->copy()->setTime($startHour, 0);

            // 95% de sesiones completadas, 5% canceladas (menos cancelaciones)
            $isCancelled = rand(1, 100) <= 5;
            $status = $isCancelled ? $cancelledStatus : $completedStatus;

            // Horas base entre 3 y 8 (más horas por sesión)
            $baseHours = rand(3, 8);
            $endDateTime = $startDateTime->copy()->addHours($baseHours);

            // Crear la jornada
            $session = FieldSession::create([
                'name' => $this->generateSessionName(),
                'academic_year_id' => $activeYear->id,
                'school_term_id' => $term->id,
                'user_id' => $teacher->id,
                'status_id' => $status->id,
                'start_datetime' => $startDateTime,
                'end_datetime' => $endDateTime,
                'location_name' => $location->name,
                'base_hours' => $baseHours,
                'description' => $this->generateSessionDescription(),
                'cancellation_reason' => $isCancelled ? $this->generateCancellationReason() : null,
            ]);

            $totalSessions++;

            // Si la sesión está cancelada, no generar asistencias
            if ($isCancelled) {
                continue;
            }

            // Seleccionar estudiantes aleatorios de diferentes secciones
            // Entre 20 y 60 estudiantes por jornada (más estudiantes para acumular más horas)
            $numStudents = rand(20, 60);
            $allEnrollments = Enrollment::where('academic_year_id', $activeYear->id)
                ->with('student')
                ->get();

            if ($allEnrollments->isEmpty()) {
                continue;
            }

            $selectedEnrollments = $allEnrollments->random(min($numStudents, $allEnrollments->count()));

            foreach ($selectedEnrollments as $enrollment) {
                // 95% asistió, 5% no asistió (mayor asistencia para simular año avanzado)
                $attended = rand(1, 100) <= 95;

                $attendance = Attendance::create([
                    'field_session_id' => $session->id,
                    'user_id' => $enrollment->student->id,
                    'academic_year_id' => $activeYear->id,
                    'attended' => $attended,
                ]);

                $totalAttendances++;

                // Si no asistió, no generar actividades
                if (! $attended) {
                    continue;
                }

                // Generar entre 3 y 6 actividades por asistencia (más actividades para acumular más horas)
                $numActivities = rand(3, 6);

                for ($j = 0; $j < $numActivities; $j++) {
                    $category = $categories->random();

                    // Horas de actividad entre 1 y las horas base de la sesión
                    $activityHours = rand(1, min($baseHours, 4));

                    AttendanceActivity::create([
                        'attendance_id' => $attendance->id,
                        'activity_category_id' => $category->id,
                        'hours' => $activityHours,
                        'notes' => $this->generateActivityNotes($category->name),
                    ]);

                    $totalActivities++;
                }
            }
        }

        $this->command->info("✅ Generadas {$totalSessions} jornadas de campo");
        $this->command->info("✅ Generadas {$totalAttendances} asistencias");
        $this->command->info("✅ Generadas {$totalActivities} actividades");
    }

    private function generateSessionName(): string
    {
        $names = [
            'Jornada Comunitaria',
            'Actividad de Campo',
            'Proyecto Socioproductivo',
            'Jornada de Trabajo',
            'Actividad Práctica',
            'Proyecto Comunitario',
            'Jornada Ambiental',
            'Actividad de Servicio',
        ];

        return $names[array_rand($names)] . ' #' . rand(1, 999);
    }

    private function generateSessionDescription(): string
    {
        $descriptions = [
            'Jornada de trabajo comunitario en el sector',
            'Actividad de mantenimiento y limpieza',
            'Proyecto de mejoramiento de espacios públicos',
            'Jornada de reforestación y cuidado ambiental',
            'Actividad de apoyo a instituciones locales',
            'Proyecto de reciclaje y gestión de residuos',
            'Jornada de pintura y embellecimiento',
            'Actividad de organización comunitaria',
            'Proyecto de huerto escolar',
            'Jornada de sensibilización ambiental',
        ];

        return $descriptions[array_rand($descriptions)];
    }

    private function generateCancellationReason(): string
    {
        $reasons = [
            'Condiciones climáticas adversas',
            'Falta de transporte',
            'Actividad institucional prioritaria',
            'Feriado no previsto',
            'Situación de emergencia',
        ];

        return $reasons[array_rand($reasons)];
    }

    private function generateActivityNotes(string $categoryName): string
    {
        $notes = [
            'Participación activa y comprometida',
            'Demostró liderazgo y trabajo en equipo',
            'Cumplió con todas las tareas asignadas',
            'Mostró iniciativa y creatividad',
            'Excelente actitud y disposición',
            'Participación adecuada en las actividades',
            'Cumplió con las tareas asignadas',
            'Buena disposición para el trabajo',
            'Colaboró con sus compañeros',
            'Completó las actividades satisfactoriamente',
            'Participación limitada',
            'Requiere mayor compromiso',
            'Necesita mejorar su actitud',
            'Debe ser más puntual',
            'Puede mejorar su desempeño',
        ];

        return $notes[array_rand($notes)];
    }
}
