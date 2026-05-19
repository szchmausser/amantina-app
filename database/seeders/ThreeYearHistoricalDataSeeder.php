<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\ActivityCategory;
use App\Models\Attendance;
use App\Models\AttendanceActivity;
use App\Models\Enrollment;
use App\Models\FieldSession;
use App\Models\FieldSessionStatus;
use App\Models\Grade;
use App\Models\GradeDefinition;
use App\Models\Location;
use App\Models\RelationshipType;
use App\Models\SchoolTerm;
use App\Models\Section;
use App\Models\SectionDefinition;
use App\Models\StudentRepresentative;
use App\Models\TeacherAssignment;
use App\Models\TermType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ThreeYearHistoricalDataSeeder extends Seeder
{
    private const PASSWORD = 'password';

    private const STUDENT_COUNT = 720;

    private const TEACHER_COUNT = 30;

    private const REPRESENTATIVE_COUNT = 360;

    private const INITIAL_STUDENTS_PER_GRADE = 100;

    private const NEW_ENTRANTS_PER_YEAR = 110;

    private const FAILURE_RATE = 0.05;

    /**
     * @var array<int, array{name: string, start_date: string, end_date: string, is_active: bool, required_hours: int}>
     */
    private const ACADEMIC_YEARS = [
        ['name' => '2023-2024', 'start_date' => '2023-09-01', 'end_date' => '2024-07-15', 'is_active' => false, 'required_hours' => 275],
        ['name' => '2024-2025', 'start_date' => '2024-09-01', 'end_date' => '2025-07-15', 'is_active' => false, 'required_hours' => 275],
        ['name' => '2025-2026', 'start_date' => '2025-09-01', 'end_date' => '2026-07-15', 'is_active' => true, 'required_hours' => 275],
    ];

    /** @var array<int, AcademicYear> */
    private array $academicYears = [];

    /** @var array<int, array<int, Grade>> */
    private array $gradesByYearAndOrder = [];

    /** @var array<int, array<int, array<int, Section>>> */
    private array $sectionsByYearAndGrade = [];

    /** @var Collection<int, User> */
    private Collection $students;

    /** @var Collection<int, User> */
    private Collection $teachers;

    /** @var Collection<int, User> */
    private Collection $representatives;

    /** @var array<int, array<int, array<int>>> */
    private array $studentIdsByYearAndGrade = [];

    /** @var array<int, array<int, array<int>>> */
    private array $failedStudentIdsByYearAndGrade = [];

    public function run(): void
    {
        mt_srand(20260519);

        $this->command->info('🌱 Creando historial académico de 3 años escolares...');
        $this->command->info('   Esto puede tardar varios minutos: se crearán usuarios, inscripciones, jornadas, asistencias y actividades.');
        $this->command->newLine();

        $this->command->info('📋 Paso 1/7: Catálogos base');
        $this->seedBaseCatalogs();
        $this->command->newLine();

        $this->command->info('👥 Paso 2/7: Usuarios históricos reutilizables');
        $this->createReusableUsers();
        $this->command->newLine();

        $this->command->info('👨‍👩‍👧 Paso 3/7: Vinculación de alumnos con representantes');
        $this->linkStudentsWithRepresentatives();
        $this->command->newLine();

        $this->command->info('🏫 Paso 4/7: Estructura académica de 3 años');
        $this->createAcademicStructure();
        $this->command->newLine();

        $this->command->info('🎓 Paso 5/7: Inscripciones y promoción anual');
        $this->enrollStudentsAcrossThreeYears();
        $this->command->newLine();

        $this->command->info('👨‍🏫 Paso 6/7: Asignaciones docentes');
        $this->assignTeachersAcrossThreeYears();
        $this->command->newLine();

        $this->command->info('📅 Paso 7/7: Jornadas, asistencias y actividades históricas');
        $this->createFieldSessionsAcrossThreeYears();

        $this->displaySummary();
    }

    private function seedBaseCatalogs(): void
    {
        $this->call([
            InstitutionSeeder::class,
            RelationshipTypeSeeder::class,
            RoleAndPermissionSeeder::class,
            FieldSessionStatusSeeder::class,
            GradeDefinitionSeeder::class,
            SectionDefinitionSeeder::class,
            ActivityCategorySeeder::class,
            LocationSeeder::class,
            HealthConditionSeeder::class,
            UserSeeder::class,
        ]);

        foreach ([
            ['name' => 'Lapso 1', 'order' => 1, 'is_active' => true],
            ['name' => 'Lapso 2', 'order' => 2, 'is_active' => true],
            ['name' => 'Lapso 3', 'order' => 3, 'is_active' => true],
        ] as $termType) {
            TermType::updateOrCreate(['name' => $termType['name']], $termType);
        }
    }

    private function createReusableUsers(): void
    {
        $password = Hash::make(self::PASSWORD);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $teacherRole = Role::firstOrCreate(['name' => 'profesor', 'guard_name' => 'web']);
        $representativeRole = Role::firstOrCreate(['name' => 'representante', 'guard_name' => 'web']);
        $studentRole = Role::firstOrCreate(['name' => 'alumno', 'guard_name' => 'web']);
        $totalUsers = self::TEACHER_COUNT + self::REPRESENTATIVE_COUNT + self::STUDENT_COUNT;
        $bar = $this->command->getOutput()->createProgressBar($totalUsers);
        $bar->start();

        $this->teachers = collect(range(1, self::TEACHER_COUNT))->map(function (int $index) use ($password, $teacherRole, $bar): User {
            $teacher = User::updateOrCreate(
                ['email' => sprintf('history.teacher.%03d@amantina.test', $index)],
                [
                    'cedula' => sprintf('71%06d', $index),
                    'name' => sprintf('Profesor Histórico %03d', $index),
                    'password' => $password,
                    'phone' => '0412'.sprintf('%07d', $index),
                    'address' => 'Dirección histórica de profesor',
                    'is_active' => true,
                    'is_transfer' => false,
                    'institution_origin' => null,
                    'email_verified_at' => now(),
                ]
            );

            $teacher->assignRole($teacherRole);
            $bar->advance();

            return $teacher;
        });

        $this->representatives = collect(range(1, self::REPRESENTATIVE_COUNT))->map(function (int $index) use ($password, $representativeRole, $bar): User {
            $representative = User::updateOrCreate(
                ['email' => sprintf('history.representative.%03d@amantina.test', $index)],
                [
                    'cedula' => sprintf('72%06d', $index),
                    'name' => sprintf('Representante Histórico %03d', $index),
                    'password' => $password,
                    'phone' => '0424'.sprintf('%07d', $index),
                    'address' => 'Dirección histórica de representante',
                    'is_active' => true,
                    'is_transfer' => false,
                    'institution_origin' => null,
                    'email_verified_at' => now(),
                ]
            );

            $representative->assignRole($representativeRole);
            $bar->advance();

            return $representative;
        });

        $this->students = collect(range(1, self::STUDENT_COUNT))->map(function (int $index) use ($password, $studentRole, $bar): User {
            $student = User::updateOrCreate(
                ['email' => sprintf('history.student.%03d@amantina.test', $index)],
                [
                    'cedula' => sprintf('73%06d', $index),
                    'name' => sprintf('Alumno Histórico %03d', $index),
                    'password' => $password,
                    'phone' => '0416'.sprintf('%07d', $index),
                    'address' => 'Dirección histórica de alumno',
                    'is_active' => true,
                    'is_transfer' => false,
                    'institution_origin' => null,
                    'email_verified_at' => now(),
                ]
            );

            $student->assignRole($studentRole);
            $bar->advance();

            return $student;
        });

        $bar->finish();
        $this->command->newLine();
        $this->command->info("   ✅ {$totalUsers} usuarios históricos preparados");
    }

    private function linkStudentsWithRepresentatives(): void
    {
        $relationshipType = RelationshipType::where('name', 'Representante')->first()
            ?? RelationshipType::orderBy('id')->first();

        if (! $relationshipType) {
            return;
        }

        $bar = $this->command->getOutput()->createProgressBar($this->students->count());
        $bar->start();

        foreach ($this->students->values() as $index => $student) {
            $representative = $this->representatives->get($index % $this->representatives->count());

            StudentRepresentative::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'representative_id' => $representative->id,
                ],
                ['relationship_type_id' => $relationshipType->id]
            );

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info("   ✅ {$this->students->count()} vínculos alumno-representante preparados");
    }

    private function createAcademicStructure(): void
    {
        AcademicYear::query()->update(['is_active' => false]);

        $gradeDefinitions = GradeDefinition::orderBy('order')->get()->keyBy('order');
        $sectionDefinitions = SectionDefinition::whereIn('name', ['A', 'B', 'C'])->get()->keyBy('name');

        foreach (self::ACADEMIC_YEARS as $yearIndex => $yearData) {
            $academicYear = AcademicYear::updateOrCreate(['name' => $yearData['name']], $yearData);
            $this->academicYears[$yearIndex] = $academicYear;

            $this->createSchoolTerms($academicYear);
            $this->gradesByYearAndOrder[$yearIndex] = [];
            $this->sectionsByYearAndGrade[$yearIndex] = [];

            for ($gradeOrder = 1; $gradeOrder <= 5; $gradeOrder++) {
                $gradeDefinition = $gradeDefinitions->get($gradeOrder);

                $grade = Grade::updateOrCreate(
                    [
                        'academic_year_id' => $academicYear->id,
                        'order' => $gradeOrder,
                    ],
                    [
                        'name' => $gradeDefinition?->name ?? "{$gradeOrder}° Año",
                        'grade_definition_id' => $gradeDefinition?->id,
                        'grade_definition_name' => $gradeDefinition?->name,
                    ]
                );

                $this->gradesByYearAndOrder[$yearIndex][$gradeOrder] = $grade;
                $this->sectionsByYearAndGrade[$yearIndex][$gradeOrder] = [];

                foreach (['A', 'B', 'C'] as $sectionName) {
                    $sectionDefinition = $sectionDefinitions->get($sectionName);

                    $this->sectionsByYearAndGrade[$yearIndex][$gradeOrder][] = Section::updateOrCreate(
                        [
                            'academic_year_id' => $academicYear->id,
                            'grade_id' => $grade->id,
                            'section_definition_name' => $sectionName,
                        ],
                        [
                            'name' => $sectionName,
                            'section_definition_id' => $sectionDefinition?->id,
                        ]
                    );
                }
            }

            $this->command->info("   ✅ {$academicYear->name}: 5 grados, 15 secciones y 3 lapsos preparados");
        }
    }

    private function createSchoolTerms(AcademicYear $academicYear): void
    {
        $termDates = [
            ['order' => 1, 'start_month_day' => '09-15', 'end_month_day' => '12-15', 'end_year_offset' => 0],
            ['order' => 2, 'start_month_day' => '01-07', 'end_month_day' => '04-10', 'end_year_offset' => 1],
            ['order' => 3, 'start_month_day' => '04-25', 'end_month_day' => '07-15', 'end_year_offset' => 1],
        ];

        $startYear = (int) CarbonImmutable::parse($academicYear->start_date)->format('Y');

        foreach ($termDates as $termData) {
            $termType = TermType::where('order', $termData['order'])->first();

            if (! $termType) {
                continue;
            }

            $startsInNextCalendarYear = $termData['order'] > 1;
            $startYearForTerm = $startsInNextCalendarYear ? $startYear + 1 : $startYear;
            $endYearForTerm = $startYear + $termData['end_year_offset'];

            SchoolTerm::updateOrCreate(
                [
                    'academic_year_id' => $academicYear->id,
                    'term_type_id' => $termType->id,
                ],
                [
                    'term_type_name' => $termType->name,
                    'start_date' => "{$startYearForTerm}-{$termData['start_month_day']}",
                    'end_date' => "{$endYearForTerm}-{$termData['end_month_day']}",
                ]
            );
        }
    }

    private function enrollStudentsAcrossThreeYears(): void
    {
        $allStudentIds = $this->students->pluck('id')->values()->all();
        $reservedStudentIds = array_slice($allStudentIds, self::INITIAL_STUDENTS_PER_GRADE * 5);

        $this->studentIdsByYearAndGrade[0] = [];
        for ($gradeOrder = 1; $gradeOrder <= 5; $gradeOrder++) {
            $offset = ($gradeOrder - 1) * self::INITIAL_STUDENTS_PER_GRADE;
            $this->studentIdsByYearAndGrade[0][$gradeOrder] = array_slice($allStudentIds, $offset, self::INITIAL_STUDENTS_PER_GRADE);
        }

        $this->enrollYear(0);

        for ($yearIndex = 1; $yearIndex <= 2; $yearIndex++) {
            $newEntrantOffset = ($yearIndex - 1) * self::NEW_ENTRANTS_PER_YEAR;
            $newEntrantIds = array_slice($reservedStudentIds, $newEntrantOffset, self::NEW_ENTRANTS_PER_YEAR);

            $this->studentIdsByYearAndGrade[$yearIndex] = $this->promoteFromPreviousYear($yearIndex - 1, $newEntrantIds);
            $this->enrollYear($yearIndex);
        }
    }

    /**
     * @param  array<int>  $newEntrantIds
     * @return array<int, array<int>>
     */
    private function promoteFromPreviousYear(int $previousYearIndex, array $newEntrantIds): array
    {
        $promotedByGrade = [];
        $this->failedStudentIdsByYearAndGrade[$previousYearIndex] = [];

        for ($gradeOrder = 1; $gradeOrder <= 5; $gradeOrder++) {
            $previousStudentIds = $this->studentIdsByYearAndGrade[$previousYearIndex][$gradeOrder] ?? [];
            $failedStudentIds = $gradeOrder < 5 ? $this->selectFailedStudentIds($previousStudentIds) : [];
            $this->failedStudentIdsByYearAndGrade[$previousYearIndex][$gradeOrder] = $failedStudentIds;

            $failedLookup = array_flip($failedStudentIds);
            $passedStudentIds = array_values(array_filter(
                $previousStudentIds,
                fn (int $studentId): bool => ! isset($failedLookup[$studentId])
            ));

            if ($gradeOrder < 5) {
                $promotedByGrade[$gradeOrder + 1] = $passedStudentIds;
            }
        }

        $nextYearStudents = [
            1 => array_merge($this->failedStudentIdsByYearAndGrade[$previousYearIndex][1] ?? [], $newEntrantIds),
            2 => array_merge($this->failedStudentIdsByYearAndGrade[$previousYearIndex][2] ?? [], $promotedByGrade[2] ?? []),
            3 => array_merge($this->failedStudentIdsByYearAndGrade[$previousYearIndex][3] ?? [], $promotedByGrade[3] ?? []),
            4 => array_merge($this->failedStudentIdsByYearAndGrade[$previousYearIndex][4] ?? [], $promotedByGrade[4] ?? []),
            5 => $promotedByGrade[5] ?? [],
        ];

        return $nextYearStudents;
    }

    /**
     * @param  array<int>  $studentIds
     * @return array<int>
     */
    private function selectFailedStudentIds(array $studentIds): array
    {
        $failureCount = (int) floor(count($studentIds) * self::FAILURE_RATE);

        if ($failureCount < 1) {
            return [];
        }

        $candidates = $studentIds;
        shuffle($candidates);

        return array_slice($candidates, 0, $failureCount);
    }

    private function enrollYear(int $yearIndex): void
    {
        $academicYear = $this->academicYears[$yearIndex];
        $totalEnrollments = collect($this->studentIdsByYearAndGrade[$yearIndex])->flatten()->count();
        $bar = $this->command->getOutput()->createProgressBar($totalEnrollments);
        $bar->start();

        foreach ($this->studentIdsByYearAndGrade[$yearIndex] as $gradeOrder => $studentIds) {
            $grade = $this->gradesByYearAndOrder[$yearIndex][$gradeOrder];
            $sections = $this->sectionsByYearAndGrade[$yearIndex][$gradeOrder];

            foreach (array_values($studentIds) as $studentIndex => $studentId) {
                $section = $sections[$studentIndex % count($sections)];

                Enrollment::updateOrCreate(
                    [
                        'academic_year_id' => $academicYear->id,
                        'user_id' => $studentId,
                    ],
                    [
                        'grade_id' => $grade->id,
                        'section_id' => $section->id,
                    ]
                );

                $bar->advance();
            }
        }

        $bar->finish();
        $this->command->newLine();
        $failedCount = collect($this->failedStudentIdsByYearAndGrade[$yearIndex - 1] ?? [])->flatten()->count();
        $this->command->info("   ✅ {$academicYear->name}: {$totalEnrollments} inscripciones preparadas, {$failedCount} reprobados simulados del año previo");
    }

    private function assignTeachersAcrossThreeYears(): void
    {
        $teacherIds = $this->teachers->pluck('id')->values()->all();

        foreach ($this->academicYears as $yearIndex => $academicYear) {
            $cursor = 0;
            $assignmentCount = 0;

            foreach ($this->sectionsByYearAndGrade[$yearIndex] as $gradeOrder => $sections) {
                foreach ($sections as $section) {
                    $teacherCount = $gradeOrder >= 4 ? 3 : 2;

                    for ($assignment = 0; $assignment < $teacherCount; $assignment++) {
                        $teacherId = $teacherIds[$cursor % count($teacherIds)];
                        $cursor++;

                        TeacherAssignment::updateOrCreate(
                            [
                                'academic_year_id' => $academicYear->id,
                                'section_id' => $section->id,
                                'user_id' => $teacherId,
                            ],
                            ['grade_id' => $section->grade_id]
                        );
                        $assignmentCount++;
                    }
                }
            }

            $this->command->info("   ✅ {$academicYear->name}: {$assignmentCount} asignaciones docentes preparadas");
        }
    }

    private function createFieldSessionsAcrossThreeYears(): void
    {
        $categories = ActivityCategory::all();
        $locations = Location::all();
        $realizedStatus = FieldSessionStatus::where('name', 'realized')->first();
        $cancelledStatus = FieldSessionStatus::where('name', 'cancelled')->first();

        if ($categories->isEmpty() || $locations->isEmpty() || ! $realizedStatus || ! $cancelledStatus) {
            $this->command->warn('No se pudieron crear jornadas históricas porque faltan catálogos base.');

            return;
        }

        foreach ($this->academicYears as $yearIndex => $academicYear) {
            if (FieldSession::where('academic_year_id', $academicYear->id)->where('name', 'like', 'Histórica %')->exists()) {
                $this->command->warn("Jornadas históricas ya existen para {$academicYear->name}; se omite duplicación.");

                continue;
            }

            $this->createFieldSessionsForYear($yearIndex, $academicYear, $categories, $locations, $realizedStatus, $cancelledStatus);
        }
    }

    private function createFieldSessionsForYear(
        int $yearIndex,
        AcademicYear $academicYear,
        Collection $categories,
        Collection $locations,
        FieldSessionStatus $realizedStatus,
        FieldSessionStatus $cancelledStatus
    ): void {
        $terms = SchoolTerm::where('academic_year_id', $academicYear->id)->get();
        $teacherIds = TeacherAssignment::where('academic_year_id', $academicYear->id)->pluck('user_id')->unique()->values();
        $studentIds = collect($this->studentIdsByYearAndGrade[$yearIndex])->flatten()->values()->all();
        $sessionCount = 120 + ($yearIndex * 20);
        $createdSessions = 0;
        $createdAttendances = 0;
        $createdActivities = 0;
        $bar = $this->command->getOutput()->createProgressBar($sessionCount);
        $bar->start();

        for ($sessionNumber = 1; $sessionNumber <= $sessionCount; $sessionNumber++) {
            $term = $terms->random();
            $category = $categories->random();
            $location = $locations->random();
            $sessionDate = $this->randomDateInsideTerm($term);
            $baseHours = mt_rand(3, 6);
            $isCancelled = mt_rand(1, 100) <= 5;

            $session = FieldSession::create([
                'name' => sprintf('Histórica %s #%03d', $academicYear->name, $sessionNumber),
                'description' => $this->sessionDescription(),
                'academic_year_id' => $academicYear->id,
                'school_term_id' => $term->id,
                'user_id' => $teacherIds->random(),
                'activity_name' => $category->name,
                'location_name' => $location->name,
                'start_datetime' => $sessionDate,
                'end_datetime' => $sessionDate->addHours($baseHours),
                'base_hours' => $baseHours,
                'status_id' => $isCancelled ? $cancelledStatus->id : $realizedStatus->id,
                'cancellation_reason' => $isCancelled ? $this->cancellationReason() : null,
            ]);
            $createdSessions++;

            if ($isCancelled) {
                $bar->advance();

                continue;
            }

            $attendeeIds = $studentIds;
            shuffle($attendeeIds);
            $attendeeIds = array_slice($attendeeIds, 0, min(count($attendeeIds), mt_rand(35, 70)));

            foreach ($attendeeIds as $studentId) {
                $attended = mt_rand(1, 100) <= 92;

                $attendance = Attendance::create([
                    'field_session_id' => $session->id,
                    'user_id' => $studentId,
                    'academic_year_id' => $academicYear->id,
                    'attended' => $attended,
                    'notes' => $attended ? null : 'Inasistencia generada para datos históricos.',
                ]);
                $createdAttendances++;

                if (! $attended) {
                    continue;
                }

                for ($activityIndex = 0; $activityIndex < mt_rand(2, 4); $activityIndex++) {
                    $activityCategory = $categories->random();

                    AttendanceActivity::create([
                        'attendance_id' => $attendance->id,
                        'activity_category_id' => $activityCategory->id,
                        'hours' => mt_rand(1, min(3, $baseHours)),
                        'notes' => $this->activityNote(),
                    ]);
                    $createdActivities++;
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info("   ✅ {$academicYear->name}: {$createdSessions} jornadas, {$createdAttendances} asistencias y {$createdActivities} actividades creadas");
    }

    private function randomDateInsideTerm(SchoolTerm $term): CarbonImmutable
    {
        $startDate = CarbonImmutable::parse($term->start_date)->setTime(mt_rand(7, 13), 0);
        $endDate = CarbonImmutable::parse($term->end_date);
        $days = max(1, $startDate->diffInDays($endDate));

        return $startDate->addDays(mt_rand(0, (int) $days));
    }

    private function sessionDescription(): string
    {
        return $this->randomItem([
            'Jornada comunitaria de producción y mantenimiento.',
            'Actividad práctica de apoyo socioproductivo.',
            'Proyecto de servicio comunitario con registro de horas.',
            'Jornada ambiental con participación estudiantil.',
            'Actividad de huerto escolar y recuperación de espacios.',
        ]);
    }

    private function cancellationReason(): string
    {
        return $this->randomItem([
            'Condiciones climáticas adversas.',
            'Suspensión institucional de actividades.',
            'Reprogramación por falta de transporte.',
        ]);
    }

    private function activityNote(): string
    {
        return $this->randomItem([
            'Participación activa y responsable.',
            'Cumplió las tareas asignadas.',
            'Buen desempeño durante la actividad.',
            'Apoyó a su equipo de trabajo.',
            'Requiere seguimiento para mejorar constancia.',
        ]);
    }

    /**
     * @param  array<int, string>  $items
     */
    private function randomItem(array $items): string
    {
        return $items[array_rand($items)];
    }

    private function displaySummary(): void
    {
        $this->command->newLine();
        $this->command->info('✅ Historial de 3 años escolares creado.');
        $this->command->info('Usuarios reutilizables:');
        $this->command->info('   Admin: admin@amantina.test / password');
        $this->command->info('   Profesor: history.teacher.001@amantina.test / password');
        $this->command->info('   Alumno: history.student.001@amantina.test / password');

        foreach ($this->academicYears as $yearIndex => $academicYear) {
            $enrollmentCount = Enrollment::where('academic_year_id', $academicYear->id)->count();
            $failedCount = collect($this->failedStudentIdsByYearAndGrade[$yearIndex] ?? [])->flatten()->count();
            $sessionCount = FieldSession::where('academic_year_id', $academicYear->id)->count();

            $this->command->info("   {$academicYear->name}: {$enrollmentCount} inscripciones, {$failedCount} reprobados simulados, {$sessionCount} jornadas.");
        }
    }
}
