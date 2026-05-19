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
use App\Models\SchoolTerm;
use App\Models\Section;
use App\Models\SectionDefinition;
use App\Models\TeacherAssignment;
use App\Models\TermType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MultiYearCompleteDataSeeder extends Seeder
{
    /**
     * Venezuelan first names pool for realistic name generation.
     */
    private const FIRST_NAMES = [
        'María', 'José', 'Luis', 'Carmen', 'Jesús', 'Rosa', 'Ana', 'Juan',
        'Pedro', 'Miguel', 'Carlos', 'Francisco', 'Javier', 'Antonio', 'Manuel',
        'Daniela', 'Gabriela', 'Andrea', 'Valentina', 'Isabella', 'Sofía',
        'Camila', 'Victoria', 'Luciana', 'Valeria', 'Mariana', 'Paola', 'Natalia',
        'Alejandra', 'Adriana', 'Patricia', 'Yolimar', 'Yusneidy', 'Keiber',
        'Wuikelman', 'Yonaiker', 'Yorbis', 'Yaritza', 'Yulimar', 'Yennifer',
        'Yorman', 'Edison', 'Wilmer', 'Eduardo', 'Fernando', 'Ricardo', 'Rafael',
        'Alberto', 'Roberto', 'Héctor', 'Andrés', 'Diego', 'Felipe', 'Gustavo',
        'Oscar', 'Mario', 'Enrique', 'Santiago', 'Gabriel', 'Alejandro', 'David',
        'Daniel', 'César', 'Elias', 'Nelson', 'Ramón', 'Victor', 'Cristian',
        'Jonathan', 'Yosmar', 'Deimar', 'Yuliana', 'Greimar', 'Yoscar',
        'Yohana', 'Marlene', 'Yolanda', 'Xiomara', 'Yamileth', 'Yessica',
        'Mileidy', 'Yusmery', 'Karina', 'Lisbeth', 'Johana', 'Dayana',
        'Emili', 'Genesis', 'Oriana', 'Barbara', 'Ariana', 'Estefania',
        'Angie', 'Yorgelis', 'Yusleidy', 'Yulitza', 'Yormary', 'Yoselin',
        'Mayerlin', 'Yenny', 'Yineska', 'Zulay', 'Yudith', 'Yelitza',
    ];

    /**
     * Venezuelan last names pool.
     */
    private const LAST_NAMES = [
        'González', 'Rodríguez', 'Pérez', 'Hernández', 'García', 'Martínez',
        'López', 'Díaz', 'Sánchez', 'Ramírez', 'Torres', 'Flores', 'Rivas',
        'Morales', 'Ortiz', 'Castillo', 'Romero', 'Moreno', 'Mendoza', 'Herrera',
        'Medina', 'Vargas', 'Castro', 'Márquez', 'Suárez', 'Gutiérrez', 'Contreras',
        'Silva', 'Navarro', 'Rojas', 'Paredes', 'Peña', 'Rivero', 'Blanco',
        'Alvarado', 'Figueroa', 'Salazar', 'Aguilar', 'Fernández', 'Guerrero',
        'Campos', 'Delgado', 'León', 'Molina', 'Vásquez', 'Jiménez', 'Rangel',
        'Zambrano', 'Escobar', 'Núñez', 'Quintero', 'Colmenares', 'Araujo',
        'Freites', 'Graterol', 'Palacios', 'Rincón', 'Cedeño', 'Velásquez',
        'Acosta', 'Barrios', 'Chacón', 'Farias', 'Guerra', 'Linares',
        'Machado', 'Padilla', 'Reyes', 'Terán', 'Urbina', 'Villegas',
        'Alfonso', 'Benavides', 'Carrillo', 'Duarte', 'Estrada', 'Franco',
        'Giménez', 'Ibarra', 'Jaramillo', 'Lugo', 'Maldonado', 'Montilla',
        'Natera', 'Ochoa', 'Parra', 'Rendón', 'Sequera', 'Tovar',
    ];

    /**
     * Academic year definitions (name, start_date, end_date, required_hours, is_active).
     */
    private const YEAR_DEFS = [
        ['name' => '2023-2024', 'start_date' => '2023-09-01', 'end_date' => '2024-07-15', 'required_hours' => 275, 'is_active' => false],
        ['name' => '2024-2025', 'start_date' => '2024-09-01', 'end_date' => '2025-07-15', 'required_hours' => 275, 'is_active' => false],
        ['name' => '2025-2026', 'start_date' => '2025-09-01', 'end_date' => '2026-07-15', 'required_hours' => 275, 'is_active' => true],
    ];

    /**
     * Term date ranges relative to each academic year's start.
     */
    private const TERM_DEFS = [
        ['term_order' => 1, 'start_offset_days' => 14, 'duration_days' => 90],
        ['term_order' => 2, 'start_offset_days' => 120, 'duration_days' => 90],
        ['term_order' => 3, 'start_offset_days' => 230, 'duration_days' => 80],
    ];

    /**
     * Track students per grade per year for multi-year progression.
     *
     * Structure: $gradeStudents[$yearIndex][$gradeOrder] = [user_ids...]
     */
    private array $gradeStudents = [];

    /**
     * Track sections per grade per year.
     *
     * Structure: $yearSections[$yearIndex][$gradeOrder] = [Section $sectionA, Section $sectionB]
     */
    private array $yearSections = [];

    /**
     * Created academic year models.
     */
    private array $years = [];

    /**
     * Teacher user models.
     */
    private array $teachers = [];

    /**
     * Student user models (all 650).
     */
    private array $students = [];

    /**
     * Total enrollment count per year for summary.
     */
    private array $enrollmentCounts = [];

    /**
     * Total field session count per year for summary.
     */
    private array $sessionCounts = [];

    /**
     * Total attendance count per year for summary.
     */
    private array $attendanceCounts = [];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        mt_srand(42); // Deterministic random for reproducible data

        $this->command->info('🌱 Iniciando semilla multi-año completa de datos de prueba...');
        $this->command->newLine();

        // Step 1: Base configuration seeders
        $this->command->info('📋 Paso 1/6: Configuración base');
        $this->seedBaseConfig();
        $this->command->newLine();

        // Step 2: Create term types
        $this->seedTermTypes();

        // Step 3: Create users (teachers, representatives, students)
        $this->command->info('👥 Paso 2/6: Creando usuarios extendidos...');
        $this->createTeachers();
        $this->createRepresentatives();
        $this->createStudents();
        $this->command->newLine();

        // Step 4: Create academic years with structure
        $this->command->info('🏫 Paso 3/6: Creando estructura académica multi-año...');
        $this->createAcademicYearsWithStructure();

        // Step 5: Multi-year student enrollment and teacher assignments
        $this->command->info('🎓 Paso 4/6: Inscribiendo estudiantes a través de 3 años...');
        $this->enrollStudentsAcrossYears();

        $this->command->info('👨‍🏫 Paso 5/6: Asignando profesores...');
        $this->assignTeachersAcrossYears();

        // Step 6: Field sessions per year
        $this->command->info('📅 Paso 6/6: Creando jornadas de campo...');
        $this->createFieldSessionsAcrossYears();

        $this->command->newLine();
        $this->displaySummary();
    }

    // ──────────────────────────────────────────────
    // Step 1: Base configuration
    // ──────────────────────────────────────────────

    private function seedBaseConfig(): void
    {
        $this->call([
            InstitutionSeeder::class,
            RelationshipTypeSeeder::class,
            RoleAndPermissionSeeder::class,
            FieldSessionStatusSeeder::class,
        ]);

        $this->call([
            UserSeeder::class,
            TestUsersSeeder::class,
        ]);

        $this->call([
            GradeDefinitionSeeder::class,
            SectionDefinitionSeeder::class,
        ]);

        $this->call([
            ActivityCategorySeeder::class,
            LocationSeeder::class,
            HealthConditionSeeder::class,
        ]);
    }

    // ──────────────────────────────────────────────
    // Term types
    // ──────────────────────────────────────────────

    private function seedTermTypes(): void
    {
        $types = [
            ['name' => 'Lapso 1', 'order' => 1, 'is_active' => true],
            ['name' => 'Lapso 2', 'order' => 2, 'is_active' => true],
            ['name' => 'Lapso 3', 'order' => 3, 'is_active' => true],
        ];

        foreach ($types as $type) {
            TermType::updateOrCreate(['name' => $type['name']], $type);
        }
    }

    // ──────────────────────────────────────────────
    // Step 2: Create users
    // ──────────────────────────────────────────────

    private function createTeachers(): void
    {
        // Clear Spatie cache before role assignment
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $count = 30;
        $bar = $this->command->getOutput()->createProgressBar($count);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $bar->setMessage('Creando 30 profesores...');
        $bar->start();

        for ($i = 1; $i <= $count; $i++) {
            $email = 'teacher'.str_pad((string) $i, 3, '0', STR_PAD_LEFT).'@amantina.test';

            $teacher = User::create([
                'cedula' => fake()->unique()->numerify('########'),
                'name' => $this->generateFullName(),
                'email' => $email,
                'password' => Hash::make('password'),
                'phone' => '0412'.fake()->numerify('#######'),
                'address' => fake()->address(),
                'is_active' => true,
                'is_transfer' => false,
                'institution_origin' => null,
                'email_verified_at' => now(),
            ]);

            $teacher->assignRole('profesor');
            $this->teachers[] = $teacher;

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info("   ✅ {$count} profesores creados");
    }

    private function createRepresentatives(): void
    {
        // Clear Spatie cache before role assignment
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $count = 2;

        for ($i = 1; $i <= $count; $i++) {
            $email = 'representative'.str_pad((string) $i, 3, '0', STR_PAD_LEFT).'@amantina.test';

            $rep = User::create([
                'cedula' => fake()->unique()->numerify('########'),
                'name' => $this->generateFullName(),
                'email' => $email,
                'password' => Hash::make('password'),
                'phone' => '0412'.fake()->numerify('#######'),
                'address' => fake()->address(),
                'is_active' => true,
                'is_transfer' => false,
                'institution_origin' => null,
                'email_verified_at' => now(),
            ]);

            $rep->assignRole('representante');
        }

        $this->command->info("   ✅ {$count} representantes creados");
    }

    private function createStudents(): void
    {
        // Clear Spatie cache before mass role assignment
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $count = 650;
        $bar = $this->command->getOutput()->createProgressBar($count);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $bar->setMessage('Creando 650 alumnos...');
        $bar->start();

        for ($i = 1; $i <= $count; $i++) {
            $email = 'student'.str_pad((string) $i, 3, '0', STR_PAD_LEFT).'@amantina.test';

            $student = User::create([
                'cedula' => fake()->unique()->numerify('########'),
                'name' => $this->generateFullName(),
                'email' => $email,
                'password' => Hash::make('password'),
                'phone' => '0412'.fake()->numerify('#######'),
                'address' => fake()->address(),
                'is_active' => true,
                'is_transfer' => false,
                'institution_origin' => null,
                'email_verified_at' => now(),
            ]);

            $student->assignRole('alumno');
            $this->students[] = $student;

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info("   ✅ {$count} alumnos creados");
    }

    // ──────────────────────────────────────────────
    // Step 3: Academic years with grades, sections, and school terms
    // ──────────────────────────────────────────────

    private function createAcademicYearsWithStructure(): void
    {
        $gradeDefinitions = GradeDefinition::orderBy('order')->get();
        $sectionDefinitions = SectionDefinition::whereIn('name', ['A', 'B'])->get()->keyBy('name');

        foreach (self::YEAR_DEFS as $yearIndex => $yearDef) {
            $year = AcademicYear::create($yearDef);
            $this->years[$yearIndex] = $year;

            // Initialize grade students tracking for this year
            for ($order = 1; $order <= 5; $order++) {
                $this->gradeStudents[$yearIndex][$order] = [];
            }

            // Create school terms
            $this->createSchoolTermsForYear($year);

            // Create grades and sections
            $this->yearSections[$yearIndex] = [];

            for ($order = 1; $order <= 5; $order++) {
                $def = $gradeDefinitions->firstWhere('order', $order);

                $grade = Grade::create([
                    'academic_year_id' => $year->id,
                    'name' => $def?->name ?? "{$order}er Año",
                    'order' => $order,
                    'grade_definition_id' => $def?->id,
                    'grade_definition_name' => $def?->name,
                ]);

                $sectionA = Section::create([
                    'academic_year_id' => $year->id,
                    'grade_id' => $grade->id,
                    'name' => 'Sección A',
                    'section_definition_id' => $sectionDefinitions['A']?->id,
                    'section_definition_name' => 'A',
                ]);

                $sectionB = Section::create([
                    'academic_year_id' => $year->id,
                    'grade_id' => $grade->id,
                    'name' => 'Sección B',
                    'section_definition_id' => $sectionDefinitions['B']?->id,
                    'section_definition_name' => 'B',
                ]);

                $this->yearSections[$yearIndex][$order] = [$sectionA, $sectionB];
            }

            $this->command->info("   ✅ Año {$year->name} creado con 5 grados, 10 secciones y 3 lapsos");
        }
    }

    private function createSchoolTermsForYear(AcademicYear $year): void
    {
        $termTypes = TermType::orderBy('order')->get();
        $yearStart = Carbon::parse($year->start_date);

        foreach (self::TERM_DEFS as $i => $termDef) {
            $termType = $termTypes->firstWhere('order', $termDef['term_order']);

            if (! $termType) {
                continue;
            }

            $startDate = $yearStart->copy()->addDays($termDef['start_offset_days']);
            $endDate = $startDate->copy()->addDays($termDef['duration_days']);

            SchoolTerm::create([
                'academic_year_id' => $year->id,
                'term_type_id' => $termType->id,
                'term_type_name' => $termType->name,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ]);
        }
    }

    // ──────────────────────────────────────────────
    // Step 4: Multi-year student enrollment
    // ──────────────────────────────────────────────

    private function enrollStudentsAcrossYears(): void
    {
        $totalStudents = count($this->students);

        // Year 1: Initial placement
        $this->command->info('   📅 Año 2023-2024: Inscripción inicial');
        $this->enrollYear1();

        // Year 2: Promotion, failure, new entrants
        $this->command->info('   📅 Año 2024-2025: Promoción con ~5% reprobados');
        $this->enrollYear2();

        // Year 3: Same promotion logic
        $this->command->info('   📅 Año 2025-2026: Promoción con ~5% reprobados');
        $this->enrollYear3();

        // Summary per year
        foreach ($this->years as $yearIndex => $year) {
            $enrollmentCount = Enrollment::where('academic_year_id', $year->id)->count();
            $this->enrollmentCounts[$yearIndex] = $enrollmentCount;
            $this->command->info("   ✅ {$year->name}: {$enrollmentCount} inscripciones");
        }
    }

    private function enrollYear1(): void
    {
        $yearIndex = 0;
        $year = $this->years[$yearIndex];

        // Distribute 500 students across 5 grades (100 each)
        $ranges = [
            1 => [0, 99],    // 1er Año
            2 => [100, 199], // 2do Año
            3 => [200, 299], // 3er Año
            4 => [300, 399], // 4to Año
            5 => [400, 499], // 5to Año
        ];

        // Students 500-649 remain unenrolled for future years

        $enrollmentBar = $this->command->getOutput()->createProgressBar(500);
        $enrollmentBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $enrollmentBar->setMessage('Inscribiendo...');
        $enrollmentBar->start();

        foreach ($ranges as $gradeOrder => [$start, $end]) {
            [$sectionA, $sectionB] = $this->yearSections[$yearIndex][$gradeOrder];
            $studentsInGrade = [];

            for ($i = $start; $i <= $end; $i++) {
                $section = ($i - $start) < 50 ? $sectionA : $sectionB;

                Enrollment::create([
                    'user_id' => $this->students[$i]->id,
                    'academic_year_id' => $year->id,
                    'grade_id' => $section->grade_id,
                    'section_id' => $section->id,
                ]);

                $studentsInGrade[] = $this->students[$i]->id;
                $enrollmentBar->advance();
            }

            $this->gradeStudents[$yearIndex][$gradeOrder] = $studentsInGrade;
        }

        $enrollmentBar->finish();
        $this->command->newLine();
    }

    private function enrollYear2(): void
    {
        $yearIndex = 1;
        $year = $this->years[$yearIndex];
        $prevYearIndex = 0;

        // Calculate promotion from year 1
        $promotion = $this->computePromotion($prevYearIndex);

        $totalEnrollments = 0;
        foreach ($promotion as $gradeOrder => $data) {
            $totalEnrollments += count($data['students']);
        }

        $enrollmentBar = $this->command->getOutput()->createProgressBar($totalEnrollments);
        $enrollmentBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $enrollmentBar->setMessage('Inscribiendo...');
        $enrollmentBar->start();

        foreach ($promotion as $gradeOrder => $data) {
            [$sectionA, $sectionB] = $this->yearSections[$yearIndex][$gradeOrder];
            $studentsInGrade = [];
            $studentIds = $data['students'];
            $count = count($studentIds);
            $half = (int) ceil($count / 2);

            for ($i = 0; $i < $count; $i++) {
                $section = $i < $half ? $sectionA : $sectionB;

                Enrollment::create([
                    'user_id' => $studentIds[$i],
                    'academic_year_id' => $year->id,
                    'grade_id' => $section->grade_id,
                    'section_id' => $section->id,
                ]);

                $studentsInGrade[] = $studentIds[$i];
                $enrollmentBar->advance();
            }

            $this->gradeStudents[$yearIndex][$gradeOrder] = $studentsInGrade;
        }

        $enrollmentBar->finish();
        $this->command->newLine();
    }

    private function enrollYear3(): void
    {
        $yearIndex = 2;
        $year = $this->years[$yearIndex];
        $prevYearIndex = 1;

        // Calculate promotion from year 2
        $promotion = $this->computePromotion($prevYearIndex);

        $totalEnrollments = 0;
        foreach ($promotion as $gradeOrder => $data) {
            $totalEnrollments += count($data['students']);
        }

        $enrollmentBar = $this->command->getOutput()->createProgressBar($totalEnrollments);
        $enrollmentBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $enrollmentBar->setMessage('Inscribiendo...');
        $enrollmentBar->start();

        foreach ($promotion as $gradeOrder => $data) {
            [$sectionA, $sectionB] = $this->yearSections[$yearIndex][$gradeOrder];
            $studentsInGrade = [];
            $studentIds = $data['students'];
            $count = count($studentIds);
            $half = (int) ceil($count / 2);

            for ($i = 0; $i < $count; $i++) {
                $section = $i < $half ? $sectionA : $sectionB;

                Enrollment::create([
                    'user_id' => $studentIds[$i],
                    'academic_year_id' => $year->id,
                    'grade_id' => $section->grade_id,
                    'section_id' => $section->id,
                ]);

                $studentsInGrade[] = $studentIds[$i];
                $enrollmentBar->advance();
            }

            $this->gradeStudents[$yearIndex][$gradeOrder] = $studentsInGrade;
        }

        $enrollmentBar->finish();
        $this->command->newLine();
    }

    /**
     * Compute the promotion/failure roster for the next year based on the previous year.
     *
     * Returns: array[gradeOrder => ['students' => [user_ids], 'failed_from' => [user_ids], 'promoted_from' => [user_ids]]]
     */
    private function computePromotion(int $prevYearIndex): array
    {
        // Pre-compute failures for all grades once (shuffle is called inside, we must do it once)
        $failures = [];
        for ($order = 1; $order <= 4; $order++) {
            $failures[$order] = $this->selectFailures(
                $this->gradeStudents[$prevYearIndex][$order] ?? [],
            );
        }

        $result = [];

        // Determine the new entrants pool
        $newEntrantPool = [];

        if ($prevYearIndex === 0) {
            // Year 1 → Year 2: new entrants are students[500..599]
            $newEntrantPool = array_slice($this->students, 500, 100);
        } elseif ($prevYearIndex === 1) {
            // Year 2 → Year 3: new entrants are students[600..649]
            $newEntrantPool = array_slice($this->students, 600, 50);
        }

        $newEntrantIds = array_map(fn (User $u) => $u->id, $newEntrantPool);

        // Grade 1: failed 1er + new entrants
        $result[1] = [
            'students' => array_merge($failures[1], $newEntrantIds),
            'failed_from' => $failures[1],
            'promoted_from' => $newEntrantIds, // these are new, not promoted
        ];

        // Grades 2-5
        for ($gradeOrder = 2; $gradeOrder <= 5; $gradeOrder++) {
            $prevLowerGradeStudents = $this->gradeStudents[$prevYearIndex][$gradeOrder - 1] ?? [];

            $failedSame = $failures[$gradeOrder] ?? [];
            $failedLower = $failures[$gradeOrder - 1] ?? [];

            // Promoted from the grade below = all students in that grade minus failures
            $failedLowerSet = array_flip($failedLower);
            $promotedFromBelow = array_values(array_filter(
                $prevLowerGradeStudents,
                fn (int $id) => ! isset($failedLowerSet[$id]),
            ));

            // For grades 2-4: failed same grade + promoted from below
            // For grade 5: only promoted from below (5to prev year graduates entirely)
            $students = $gradeOrder === 5
                ? $promotedFromBelow
                : array_merge($failedSame, $promotedFromBelow);

            $result[$gradeOrder] = [
                'students' => $students,
                'failed_from' => $gradeOrder === 5 ? [] : $failedSame,
                'promoted_from' => $promotedFromBelow,
            ];
        }

        return $result;
    }

    /**
     * Select ~5% of students as failures from a grade.
     */
    private function selectFailures(array $studentIds): array
    {
        $count = count($studentIds);

        if ($count === 0) {
            return [];
        }

        $numFailures = max(0, (int) floor($count * 0.05));

        if ($numFailures === 0) {
            return [];
        }

        // Shuffle deterministically and take first N
        $shuffled = $studentIds;
        shuffle($shuffled);

        return array_slice($shuffled, 0, $numFailures);
    }

    // ──────────────────────────────────────────────
    // Step 5: Teacher assignments per year
    // ──────────────────────────────────────────────

    private function assignTeachersAcrossYears(): void
    {
        $teacherIds = array_map(fn (User $t) => $t->id, $this->teachers);

        foreach ($this->years as $yearIndex => $year) {
            $this->assignTeachersForYear($year, $yearIndex, $teacherIds);
        }
    }

    private function assignTeachersForYear(AcademicYear $year, int $yearIndex, array $teacherIds): void
    {
        // Shuffle teachers for this year so assignments vary
        $shuffled = $teacherIds;
        shuffle($shuffled);
        $teacherPtr = 0;
        $teacherCount = count($shuffled);

        $assignments = 0;

        foreach ($this->yearSections[$yearIndex] as $gradeOrder => [$sectionA, $sectionB]) {
            // 2-3 teachers per section
            foreach ([$sectionA, $sectionB] as $section) {
                $numTeachers = rand(2, 3);

                for ($i = 0; $i < $numTeachers; $i++) {
                    $teacherId = $shuffled[$teacherPtr % $teacherCount];
                    $teacherPtr++;

                    TeacherAssignment::create([
                        'user_id' => $teacherId,
                        'academic_year_id' => $year->id,
                        'grade_id' => $section->grade_id,
                        'section_id' => $section->id,
                    ]);

                    $assignments++;
                }
            }
        }

        $this->command->info("   ✅ {$year->name}: {$assignments} asignaciones docentes");
    }

    // ──────────────────────────────────────────────
    // Step 6: Field sessions per year
    // ──────────────────────────────────────────────

    private function createFieldSessionsAcrossYears(): void
    {
        $categories = ActivityCategory::all();
        $locations = Location::all();
        $realizedStatus = FieldSessionStatus::where('name', 'realized')->first();
        $cancelledStatus = FieldSessionStatus::where('name', 'cancelled')->first();

        foreach ($this->years as $yearIndex => $year) {
            $this->createFieldSessionsForYear(
                $year,
                $yearIndex,
                $categories,
                $locations,
                $realizedStatus,
                $cancelledStatus
            );
        }
    }

    private function createFieldSessionsForYear(
        AcademicYear $year,
        int $yearIndex,
        object $categories,
        object $locations,
        ?FieldSessionStatus $realizedStatus,
        ?FieldSessionStatus $cancelledStatus
    ): void {
        $terms = SchoolTerm::where('academic_year_id', $year->id)->get();

        if ($terms->isEmpty() || ! $realizedStatus || ! $cancelledStatus) {
            $this->command->warn("   ⚠️ {$year->name}: Faltan datos para crear jornadas");

            return;
        }

        // Get teachers assigned to this year
        $teacherAssignments = TeacherAssignment::where('academic_year_id', $year->id)
            ->with('teacher')
            ->get();
        $teachers = $teacherAssignments->pluck('teacher')->unique('id');

        if ($teachers->isEmpty()) {
            $this->command->warn("   ⚠️ {$year->name}: No hay profesores asignados");

            return;
        }

        // Get enrolled students for this year
        $enrollmentIds = Enrollment::where('academic_year_id', $year->id)
            ->pluck('user_id')
            ->toArray();

        if ($enrollmentIds === []) {
            $this->command->warn("   ⚠️ {$year->name}: No hay estudiantes inscritos");

            return;
        }

        $numSessions = rand(40, 60);
        $totalSessions = 0;
        $totalAttendances = 0;

        $bar = $this->command->getOutput()->createProgressBar($numSessions);
        $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
        $bar->setMessage("Jornadas {$year->name}...");
        $bar->start();

        for ($i = 0; $i < $numSessions; $i++) {
            $term = $terms->random();
            /** @var User $teacher */
            $teacher = $teachers->random();
            /** @var Location $location */
            $location = $locations->random();

            // Random date within the term
            $startDate = Carbon::parse($term->start_date);
            $endDate = Carbon::parse($term->end_date);
            $sessionDate = $startDate->copy()->addDays(rand(0, max(0, (int) $startDate->diffInDays($endDate))));
            $startHour = rand(7, 14);
            $startDateTime = $sessionDate->copy()->setTime($startHour, 0);

            $isCancelled = rand(1, 100) <= 5;
            $status = $isCancelled ? $cancelledStatus : $realizedStatus;
            $baseHours = rand(2, 6);
            $endDateTime = $startDateTime->copy()->addHours($baseHours);

            /** @var ActivityCategory $category */
            $category = $categories->random();

            $session = FieldSession::create([
                'name' => $this->generateSessionName(),
                'academic_year_id' => $year->id,
                'school_term_id' => $term->id,
                'user_id' => $teacher->id,
                'activity_name' => $category->name,
                'location_name' => $location->name,
                'start_datetime' => $startDateTime,
                'end_datetime' => $endDateTime,
                'base_hours' => $baseHours,
                'status_id' => $status->id,
                'description' => $this->generateSessionDescription(),
                'cancellation_reason' => $isCancelled ? $this->generateCancellationReason() : null,
            ]);

            $totalSessions++;

            if ($isCancelled) {
                $bar->advance();

                continue;
            }

            // 10-30 random attendances
            $numAttendees = rand(10, 30);
            $shuffledEnrollmentIds = $enrollmentIds;
            shuffle($shuffledEnrollmentIds);
            $selectedStudentIds = array_slice($shuffledEnrollmentIds, 0, min($numAttendees, count($enrollmentIds)));

            foreach ($selectedStudentIds as $studentId) {
                $attended = rand(1, 100) <= 90;

                $attendance = Attendance::create([
                    'field_session_id' => $session->id,
                    'user_id' => (int) $studentId,
                    'academic_year_id' => $year->id,
                    'attended' => $attended,
                ]);

                $totalAttendances++;

                if (! $attended) {
                    continue;
                }

                // 2-4 attendance activities
                $numActivities = rand(2, 4);

                for ($j = 0; $j < $numActivities; $j++) {
                    /** @var ActivityCategory $actCategory */
                    $actCategory = $categories->random();

                    AttendanceActivity::create([
                        'attendance_id' => $attendance->id,
                        'activity_category_id' => $actCategory->id,
                        'hours' => rand(1, 3),
                        'notes' => $this->generateActivityNotes(),
                    ]);
                }
            }

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();

        $this->sessionCounts[$yearIndex] = $totalSessions;
        $this->attendanceCounts[$yearIndex] = $totalAttendances;

        $this->command->info("   ✅ {$year->name}: {$totalSessions} jornadas, {$totalAttendances} asistencias");
    }

    // ──────────────────────────────────────────────
    // Summary
    // ──────────────────────────────────────────────

    private function displaySummary(): void
    {
        $this->command->info('═══════════════════════════════════════════');
        $this->command->info('  RESUMEN DE SEMILLA MULTI-AÑO');
        $this->command->info('═══════════════════════════════════════════');
        $this->command->newLine();

        $this->command->info('👥 Total alumnos creados: '.count($this->students));

        $this->command->newLine();

        foreach ($this->years as $yearIndex => $year) {
            $enrollments = $this->enrollmentCounts[$yearIndex] ?? 0;
            $sessions = $this->sessionCounts[$yearIndex] ?? 0;
            $attendances = $this->attendanceCounts[$yearIndex] ?? 0;

            $this->command->info("📅 {$year->name}:");
            $this->command->info("   - Inscripciones: {$enrollments}");
            $this->command->info("   - Jornadas: {$sessions}");
            $this->command->info("   - Asistencias: {$attendances}");
            $this->command->newLine();
        }

        $this->command->info('🔑 Credenciales de acceso:');
        $this->command->info('   Admin:        admin@amantina.test / password');
        $this->command->info('   Profesor:     teacher001@amantina.test / password');
        $this->command->info('   Alumno:       student001@amantina.test / password');
        $this->command->newLine();
        $this->command->info('✅ ¡Semilla multi-año completada exitosamente!');
    }

    // ──────────────────────────────────────────────
    // Helper: Name generation
    // ──────────────────────────────────────────────

    private function generateFullName(): string
    {
        $firstName = self::FIRST_NAMES[array_rand(self::FIRST_NAMES)];
        $lastName1 = self::LAST_NAMES[array_rand(self::LAST_NAMES)];
        $lastName2 = self::LAST_NAMES[array_rand(self::LAST_NAMES)];

        return "{$firstName} {$lastName1} {$lastName2}";
    }

    // ──────────────────────────────────────────────
    // Helper: Session name/description generation
    // ──────────────────────────────────────────────

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

        return $names[array_rand($names)].' #'.rand(1, 999);
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

    private function generateActivityNotes(): string
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
        ];

        return $notes[array_rand($notes)];
    }
}
