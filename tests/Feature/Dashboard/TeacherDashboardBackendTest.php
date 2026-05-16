<?php

namespace Tests\Feature\Dashboard;

use App\Models\AcademicYear;
use App\Models\ActivityCategory;
use App\Models\Attendance;
use App\Models\AttendanceActivity;
use App\Models\Enrollment;
use App\Models\FieldSession;
use App\Models\FieldSessionStatus;
use App\Models\Grade;
use App\Models\HealthCondition;
use App\Models\Section;
use App\Models\StudentHealthRecord;
use App\Models\TeacherAssignment;
use App\Models\User;
use App\Services\HourAccumulatorService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TeacherDashboardBackendTest extends TestCase
{
    use RefreshDatabase;

    protected AcademicYear $activeYear;

    protected User $teacher;

    protected Section $sectionA;

    protected Section $sectionB;

    protected Grade $grade;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->activeYear = AcademicYear::factory()->create([
            'is_active' => true,
            'required_hours' => 100,
            'name' => '2026',
        ]);

        $this->grade = Grade::factory()->create([
            'name' => '5to Año',
            'academic_year_id' => $this->activeYear->id,
        ]);

        $this->sectionA = Section::factory()->create([
            'name' => 'Sección A',
            'grade_id' => $this->grade->id,
            'academic_year_id' => $this->activeYear->id,
        ]);

        $this->sectionB = Section::factory()->create([
            'name' => 'Sección B',
            'grade_id' => $this->grade->id,
            'academic_year_id' => $this->activeYear->id,
        ]);

        $this->teacher = User::factory()->create();
        $this->teacher->assignRole('profesor');

        // Assign teacher to both sections
        TeacherAssignment::create([
            'user_id' => $this->teacher->id,
            'section_id' => $this->sectionA->id,
            'academic_year_id' => $this->activeYear->id,
            'grade_id' => $this->grade->id,
        ]);

        TeacherAssignment::create([
            'user_id' => $this->teacher->id,
            'section_id' => $this->sectionB->id,
            'academic_year_id' => $this->activeYear->id,
            'grade_id' => $this->grade->id,
        ]);
    }

    /**
     * Helper: Create an enrolled student with attendance hours in a section.
     */
    protected function createStudentWithHours(
        Section $section,
        float $hours,
        ?User $student = null,
        ?User $teacher = null,
        ?AcademicYear $year = null
    ): User {
        $student ??= User::factory()->create();
        $student->assignRole('alumno');
        $teacher ??= $this->teacher;
        $year ??= $this->activeYear;

        Enrollment::create([
            'user_id' => $student->id,
            'section_id' => $section->id,
            'academic_year_id' => $year->id,
            'grade_id' => $section->grade_id,
        ]);

        if ($hours > 0) {
            $session = FieldSession::factory()->create([
                'user_id' => $teacher->id,
                'academic_year_id' => $year->id,
                'base_hours' => 0,
                'start_datetime' => now()->subDays(7),
            ]);

            $attendance = Attendance::create([
                'field_session_id' => $session->id,
                'user_id' => $student->id,
                'academic_year_id' => $year->id,
                'attended' => true,
            ]);

            AttendanceActivity::create([
                'attendance_id' => $attendance->id,
                'activity_category_id' => ActivityCategory::factory()->create()->id,
                'hours' => $hours,
            ]);
        }

        return $student;
    }

    // ============================================
    // TASK 1.1: getTeacherSectionIds()
    // ============================================

    public function test_get_teacher_section_ids_returns_assigned_sections(): void
    {
        $service = app(HourAccumulatorService::class);
        $result = $service->getTeacherSectionIds($this->teacher->id);

        expect($result)->toBeCollection();
        expect($result->toArray())->toEqualCanonicalizing([
            $this->sectionA->id,
            $this->sectionB->id,
        ]);
    }

    public function test_get_teacher_section_ids_excludes_deleted_assignments(): void
    {
        // Soft-delete one assignment
        TeacherAssignment::where('user_id', $this->teacher->id)
            ->where('section_id', $this->sectionB->id)
            ->delete();

        $service = app(HourAccumulatorService::class);
        $result = $service->getTeacherSectionIds($this->teacher->id);

        expect($result->count())->toBe(1);
        expect($result->first())->toBe($this->sectionA->id);
    }

    public function test_get_teacher_section_ids_returns_empty_for_teacher_without_assignments(): void
    {
        $otherTeacher = User::factory()->create();
        $otherTeacher->assignRole('profesor');

        $service = app(HourAccumulatorService::class);
        $result = $service->getTeacherSectionIds($otherTeacher->id);

        expect($result)->toBeCollection();
        expect($result->isEmpty())->toBeTrue();
    }

    // ============================================
    // TASK 1.2: getTeacherStudentDistribution()
    // ============================================

    public function test_distribution_counts_students_by_percentage(): void
    {
        // On Track: ≥80% → 80h
        $this->createStudentWithHours($this->sectionA, 80);
        // In Progress: 40–79% → 50h
        $this->createStudentWithHours($this->sectionA, 50);
        // At Risk: <40% → 30h
        $this->createStudentWithHours($this->sectionA, 30);
        // Zero Hours: 0h
        $this->createStudentWithHours($this->sectionA, 0);

        $service = app(HourAccumulatorService::class);
        $result = $service->getTeacherStudentDistribution($this->teacher->id, $this->activeYear->id);

        expect($result)->toBeArray();
        expect($result['totalStudents'])->toBe(4);
        expect($result['distribution'])->toMatchArray([
            'onTrack' => 1,
            'inProgress' => 1,
            'atRisk' => 1,
            'zeroHours' => 1,
        ]);
    }

    public function test_distribution_returns_lists_of_students_per_category(): void
    {
        $onTrackStudent = $this->createStudentWithHours($this->sectionA, 90);
        $atRiskStudent = $this->createStudentWithHours($this->sectionA, 30);
        $zeroHourStudent = $this->createStudentWithHours($this->sectionA, 0);

        $service = app(HourAccumulatorService::class);
        $result = $service->getTeacherStudentDistribution($this->teacher->id, $this->activeYear->id);

        // Verify onTrackStudents list
        expect($result['onTrackStudents'])->toHaveCount(1);
        expect($result['onTrackStudents'][0]['id'])->toBe($onTrackStudent->id);
        expect($result['onTrackStudents'][0]['name'])->toBe($onTrackStudent->name);
        expect($result['onTrackStudents'][0]['hours'])->toBe(90.0);
        expect($result['onTrackStudents'][0]['quota'])->toBe(100.0);
        expect($result['onTrackStudents'][0]['percentage'])->toBe(90.0);
        expect($result['onTrackStudents'][0]['status'])->toBe('green');

        // Verify atRiskStudents list
        expect($result['atRiskStudents'])->toHaveCount(1);
        expect($result['atRiskStudents'][0]['id'])->toBe($atRiskStudent->id);
        expect($result['atRiskStudents'][0]['status'])->toBe('red');

        // Verify studentsWithNoHours list
        expect($result['studentsWithNoHours'])->toHaveCount(1);
        expect($result['studentsWithNoHours'][0]['id'])->toBe($zeroHourStudent->id);
        expect($result['studentsWithNoHours'][0]['hours'])->toBe(0.0);
    }

    public function test_distribution_sorts_at_risk_by_percentage_ascending(): void
    {
        $worst = $this->createStudentWithHours($this->sectionA, 10);  // 10%
        $better = $this->createStudentWithHours($this->sectionA, 30); // 30%

        $service = app(HourAccumulatorService::class);
        $result = $service->getTeacherStudentDistribution($this->teacher->id, $this->activeYear->id);

        expect($result['atRiskStudents'])->toHaveCount(2);
        expect($result['atRiskStudents'][0]['id'])->toBe($worst->id);  // Worst first
        expect($result['atRiskStudents'][1]['id'])->toBe($better->id);
    }

    public function test_distribution_outstanding_students_100_percent_or_more(): void
    {
        $outstanding = $this->createStudentWithHours($this->sectionA, 120); // 120% ≥ 100%
        $exact100 = $this->createStudentWithHours($this->sectionA, 100);    // 100%

        $service = app(HourAccumulatorService::class);
        $result = $service->getTeacherStudentDistribution($this->teacher->id, $this->activeYear->id);

        expect($result['outstandingStudents'])->toHaveCount(2);
        // Sorted by hours descending
        expect($result['outstandingStudents'][0]['id'])->toBe($outstanding->id);
        expect($result['outstandingStudents'][1]['id'])->toBe($exact100->id);
    }

    public function test_distribution_top_students_sorted_by_hours_descending(): void
    {
        $high = $this->createStudentWithHours($this->sectionA, 120);
        $mid = $this->createStudentWithHours($this->sectionA, 80);
        $low = $this->createStudentWithHours($this->sectionA, 50);

        $service = app(HourAccumulatorService::class);
        $result = $service->getTeacherStudentDistribution($this->teacher->id, $this->activeYear->id);

        expect($result['topStudents'])->toHaveCount(3);
        expect($result['topStudents'][0]['id'])->toBe($high->id);
        expect($result['topStudents'][1]['id'])->toBe($mid->id);
        expect($result['topStudents'][2]['id'])->toBe($low->id);
    }

    public function test_distribution_only_includes_teachers_students(): void
    {
        // Student in teacher's section A
        $this->createStudentWithHours($this->sectionA, 80);

        // Student in section C (not assigned to teacher)
        $unassignedGrade = Grade::factory()->create();
        $sectionC = Section::factory()->create([
            'grade_id' => $unassignedGrade->id,
            'academic_year_id' => $this->activeYear->id,
        ]);

        $otherStudent = User::factory()->create();
        $otherStudent->assignRole('alumno');
        Enrollment::create([
            'user_id' => $otherStudent->id,
            'section_id' => $sectionC->id,
            'academic_year_id' => $this->activeYear->id,
            'grade_id' => $unassignedGrade->id,
        ]);

        $service = app(HourAccumulatorService::class);
        $result = $service->getTeacherStudentDistribution($this->teacher->id, $this->activeYear->id);

        // Only the student from the teacher's sections should appear
        expect($result['totalStudents'])->toBe(1);
        $allStudentIds = array_merge(
            array_column($result['onTrackStudents'], 'id'),
            array_column($result['inProgressStudents'], 'id'),
            array_column($result['atRiskStudents'], 'id'),
            array_column($result['studentsWithNoHours'], 'id'),
        );
        expect($allStudentIds)->not->toContain($otherStudent->id);
    }

    public function test_distribution_includes_section_and_grade_info(): void
    {
        $student = $this->createStudentWithHours($this->sectionA, 85);

        $service = app(HourAccumulatorService::class);
        $result = $service->getTeacherStudentDistribution($this->teacher->id, $this->activeYear->id);

        expect($result['onTrackStudents'][0]['sectionName'])->toBe('Sección A');
        expect($result['onTrackStudents'][0]['gradeName'])->toBe('5to Año');
    }

    public function test_distribution_handles_teacher_with_no_students(): void
    {
        // Teacher assigned to sections but no students enrolled
        $service = app(HourAccumulatorService::class);
        $result = $service->getTeacherStudentDistribution($this->teacher->id, $this->activeYear->id);

        expect($result['totalStudents'])->toBe(0);
        expect($result['distribution'])->toMatchArray([
            'onTrack' => 0,
            'inProgress' => 0,
            'atRisk' => 0,
            'zeroHours' => 0,
        ]);
        expect($result['onTrackStudents'])->toBeArray()->toBeEmpty();
        expect($result['atRiskStudents'])->toBeArray()->toBeEmpty();
        expect($result['outstandingStudents'])->toBeArray()->toBeEmpty();
        expect($result['topStudents'])->toBeArray()->toBeEmpty();
    }

    public function test_distribution_filters_by_academic_year(): void
    {
        // Student in active year
        $this->createStudentWithHours($this->sectionA, 80, null, null, $this->activeYear);

        // Create another year
        $otherYear = AcademicYear::factory()->create([
            'is_active' => false,
            'required_hours' => 80,
            'name' => '2025',
        ]);
        $otherStudent = User::factory()->create();
        $otherStudent->assignRole('alumno');
        Enrollment::create([
            'user_id' => $otherStudent->id,
            'section_id' => $this->sectionA->id,
            'academic_year_id' => $otherYear->id,
            'grade_id' => $this->grade->id,
        ]);

        // Query active year only
        $service = app(HourAccumulatorService::class);
        $result = $service->getTeacherStudentDistribution($this->teacher->id, $this->activeYear->id);

        expect($result['totalStudents'])->toBe(1);
    }

    // ============================================
    // TASK 1.3: getTeacherUpcomingSessions()
    // ============================================

    public function test_upcoming_sessions_only_includes_future_sessions(): void
    {
        $status = FieldSessionStatus::first() ?? FieldSessionStatus::factory()->create(['name' => 'planned']);

        // Past session — should NOT appear
        FieldSession::factory()->create([
            'name' => 'Sesión Pasada',
            'user_id' => $this->teacher->id,
            'academic_year_id' => $this->activeYear->id,
            'start_datetime' => now()->subDays(10),
            'status_id' => $status->id,
            'location_name' => 'Huerto',
        ]);

        // Future session — SHOULD appear
        $futureSession = FieldSession::factory()->create([
            'name' => 'Sesión Futura',
            'user_id' => $this->teacher->id,
            'academic_year_id' => $this->activeYear->id,
            'start_datetime' => now()->addDays(5),
            'status_id' => $status->id,
            'location_name' => 'Cancha',
        ]);

        $service = app(HourAccumulatorService::class);
        $result = $service->getTeacherUpcomingSessions($this->teacher->id, $this->activeYear->id);

        expect($result)->toBeArray();
        expect($result)->toHaveCount(1);
        expect($result[0]['id'])->toBe($futureSession->id);
        expect($result[0]['name'])->toBe('Sesión Futura');
        expect($result[0]['location'])->toBe('Cancha');
    }

    public function test_upcoming_sessions_are_ordered_by_date_ascending(): void
    {
        $status = FieldSessionStatus::first() ?? FieldSessionStatus::factory()->create(['name' => 'planned']);

        $laterSession = FieldSession::factory()->create([
            'name' => 'Sesión Tardía',
            'user_id' => $this->teacher->id,
            'academic_year_id' => $this->activeYear->id,
            'start_datetime' => now()->addDays(10),
            'status_id' => $status->id,
        ]);

        $soonerSession = FieldSession::factory()->create([
            'name' => 'Sesión Próxima',
            'user_id' => $this->teacher->id,
            'academic_year_id' => $this->activeYear->id,
            'start_datetime' => now()->addDays(2),
            'status_id' => $status->id,
        ]);

        $service = app(HourAccumulatorService::class);
        $result = $service->getTeacherUpcomingSessions($this->teacher->id, $this->activeYear->id);

        expect($result)->toHaveCount(2);
        expect($result[0]['id'])->toBe($soonerSession->id);  // Sooner first
        expect($result[1]['id'])->toBe($laterSession->id);
    }

    public function test_upcoming_sessions_limited_to_10(): void
    {
        $status = FieldSessionStatus::first() ?? FieldSessionStatus::factory()->create(['name' => 'planned']);

        for ($i = 0; $i < 15; $i++) {
            FieldSession::factory()->create([
                'name' => "Sesión {$i}",
                'user_id' => $this->teacher->id,
                'academic_year_id' => $this->activeYear->id,
                'start_datetime' => now()->addDays($i + 1),
                'status_id' => $status->id,
            ]);
        }

        $service = app(HourAccumulatorService::class);
        $result = $service->getTeacherUpcomingSessions($this->teacher->id, $this->activeYear->id);

        expect($result)->toHaveCount(10);
    }

    public function test_upcoming_sessions_includes_status_and_section_names(): void
    {
        $status = FieldSessionStatus::factory()->create(['name' => 'planned']);

        $session = FieldSession::factory()->create([
            'name' => 'Sesión Planificada',
            'user_id' => $this->teacher->id,
            'academic_year_id' => $this->activeYear->id,
            'start_datetime' => now()->addDays(3),
            'status_id' => $status->id,
            'location_name' => 'Comunidad',
        ]);

        $service = app(HourAccumulatorService::class);
        $result = $service->getTeacherUpcomingSessions($this->teacher->id, $this->activeYear->id);

        expect($result[0]['statusName'])->toBe('planned');
        expect($result[0]['sectionName'])->toBeString();
        expect($result[0]['date'])->toBeString();
    }

    public function test_upcoming_sessions_empty_when_no_future_sessions(): void
    {
        $service = app(HourAccumulatorService::class);
        $result = $service->getTeacherUpcomingSessions($this->teacher->id, $this->activeYear->id);

        expect($result)->toBeArray()->toBeEmpty();
    }

    public function test_upcoming_sessions_excludes_other_teachers_sessions(): void
    {
        $otherTeacher = User::factory()->create();
        $otherTeacher->assignRole('profesor');
        $status = FieldSessionStatus::first() ?? FieldSessionStatus::factory()->create(['name' => 'planned']);

        // Other teacher's session
        FieldSession::factory()->create([
            'name' => 'Sesión Otro Profesor',
            'user_id' => $otherTeacher->id,
            'academic_year_id' => $this->activeYear->id,
            'start_datetime' => now()->addDays(3),
            'status_id' => $status->id,
        ]);

        $service = app(HourAccumulatorService::class);
        $result = $service->getTeacherUpcomingSessions($this->teacher->id, $this->activeYear->id);

        expect($result)->toBeEmpty();
    }

    public function test_upcoming_sessions_filters_by_year(): void
    {
        $status = FieldSessionStatus::first() ?? FieldSessionStatus::factory()->create(['name' => 'planned']);

        $otherYear = AcademicYear::factory()->create([
            'is_active' => false,
            'required_hours' => 80,
        ]);

        // Session in other year
        FieldSession::factory()->create([
            'name' => 'Sesión Otro Año',
            'user_id' => $this->teacher->id,
            'academic_year_id' => $otherYear->id,
            'start_datetime' => now()->addDays(5),
            'status_id' => $status->id,
        ]);

        // Query active year only
        $service = app(HourAccumulatorService::class);
        $result = $service->getTeacherUpcomingSessions($this->teacher->id, $this->activeYear->id);

        expect($result)->toBeEmpty();
    }

    // ============================================
    // TASK 1.4: Enhance Low Attendance Query
    // ============================================

    public function test_low_attendance_includes_total_hours(): void
    {
        // Create a student with 2 attendances (low attendance threshold)
        $student = User::factory()->create();
        $student->assignRole('alumno');

        Enrollment::create([
            'user_id' => $student->id,
            'section_id' => $this->sectionA->id,
            'academic_year_id' => $this->activeYear->id,
            'grade_id' => $this->grade->id,
        ]);

        // Create 2 sessions with attendance + hours
        for ($i = 0; $i < 2; $i++) {
            $session = FieldSession::factory()->create([
                'name' => "Sesión {$i}",
                'user_id' => $this->teacher->id,
                'academic_year_id' => $this->activeYear->id,
                'start_datetime' => now()->subDays(10 + $i),
            ]);

            $attendance = Attendance::create([
                'field_session_id' => $session->id,
                'user_id' => $student->id,
                'academic_year_id' => $this->activeYear->id,
                'attended' => true,
            ]);

            AttendanceActivity::create([
                'attendance_id' => $attendance->id,
                'activity_category_id' => ActivityCategory::factory()->create()->id,
                'hours' => 3.5,
            ]);
        }

        $data = app(HourAccumulatorService::class)->getTeacherDashboard($this->teacher->id, $this->activeYear->id);

        expect($data['lowAttendanceStudents'])->toHaveCount(1);
        expect($data['lowAttendanceStudents'][0]['studentId'])->toBe($student->id);
        expect($data['lowAttendanceStudents'][0]['attendanceCount'])->toBe(2);
        expect($data['lowAttendanceStudents'][0]['totalHours'])->toBe(7.0); // 2 × 3.5
    }

    // ============================================
    // TASK 1.5: Enhance Health Reminders
    // ============================================

    public function test_health_reminders_include_severity(): void
    {
        $student = User::factory()->create();
        $student->assignRole('alumno');

        Enrollment::create([
            'user_id' => $student->id,
            'section_id' => $this->sectionA->id,
            'academic_year_id' => $this->activeYear->id,
            'grade_id' => $this->grade->id,
        ]);

        $condition = HealthCondition::factory()->create(['name' => 'Asma']);
        StudentHealthRecord::create([
            'user_id' => $student->id,
            'health_condition_id' => $condition->id,
            'received_by' => $this->teacher->id,
            'received_at' => now()->subDays(5),
        ]);

        // Create a session where student attended
        $session = FieldSession::factory()->create([
            'user_id' => $this->teacher->id,
            'academic_year_id' => $this->activeYear->id,
            'start_datetime' => now()->subDays(3),
        ]);

        Attendance::create([
            'field_session_id' => $session->id,
            'user_id' => $student->id,
            'academic_year_id' => $this->activeYear->id,
            'attended' => true,
        ]);

        $data = app(HourAccumulatorService::class)->getTeacherDashboard($this->teacher->id, $this->activeYear->id);

        expect($data['healthReminders'])->toHaveCount(1);
        expect($data['healthReminders'][0]['studentName'])->toBe($student->name);
        expect($data['healthReminders'][0]['conditionName'])->toBe('Asma');
        expect($data['healthReminders'][0]['severity'])->toBe('medium'); // default: no severity column
        expect($data['healthReminders'][0]['lastSessionDate'])->toBeString();
        expect($data['healthReminders'][0]['daysSinceLastSession'])->toBeInt();
        // Since session was 3 days ago, daysSinceLastSession should be >= 3
        expect($data['healthReminders'][0]['daysSinceLastSession'])->toBeGreaterThanOrEqual(3);
    }

    // ============================================
    // TASK 1.6: Enhance Category Distribution
    // ============================================

    public function test_category_distribution_includes_count_and_min_required_hours(): void
    {
        $student = User::factory()->create();
        $student->assignRole('alumno');

        Enrollment::create([
            'user_id' => $student->id,
            'section_id' => $this->sectionA->id,
            'academic_year_id' => $this->activeYear->id,
            'grade_id' => $this->grade->id,
        ]);

        $category = ActivityCategory::factory()->create(['name' => 'Siembra']);

        // Create 2 attendances with the same category
        for ($i = 0; $i < 2; $i++) {
            $session = FieldSession::factory()->create([
                'user_id' => $this->teacher->id,
                'academic_year_id' => $this->activeYear->id,
                'start_datetime' => now()->subDays(5 + $i),
            ]);

            $attendance = Attendance::create([
                'field_session_id' => $session->id,
                'user_id' => $student->id,
                'academic_year_id' => $this->activeYear->id,
                'attended' => true,
            ]);

            AttendanceActivity::create([
                'attendance_id' => $attendance->id,
                'activity_category_id' => $category->id,
                'hours' => 2.0,
            ]);
        }

        $data = app(HourAccumulatorService::class)->getTeacherDashboard($this->teacher->id, $this->activeYear->id);

        expect($data['categoryDistribution'])->toHaveCount(1);
        expect($data['categoryDistribution'][0]['categoryName'])->toBe('Siembra');
        expect($data['categoryDistribution'][0]['totalHours'])->toBe(4.0);
        expect($data['categoryDistribution'][0]['count'])->toBe(2); // 2 attendances
        expect($data['categoryDistribution'][0]['minRequiredHours'])->toBeNull(); // no column exists
    }
}
