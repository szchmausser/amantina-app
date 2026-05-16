<?php

namespace Tests\Feature\Dashboard;

use App\Models\AcademicYear;
use App\Models\ActivityCategory;
use App\Models\Attendance;
use App\Models\AttendanceActivity;
use App\Models\Enrollment;
use App\Models\FieldSession;
use App\Models\Grade;
use App\Models\Section;
use App\Models\TeacherAssignment;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TeacherDistributionTest extends TestCase
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

        $this->teacher = User::factory()->create(['name' => 'Prof. Test']);
        $this->teacher->assignRole('profesor');

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
     * Helper: Create an enrolled student with attendance hours.
     */
    protected function createStudentWithHours(
        Section $section,
        float $hours,
        ?User $student = null
    ): User {
        $student ??= User::factory()->create();
        $student->assignRole('alumno');

        Enrollment::create([
            'user_id' => $student->id,
            'section_id' => $section->id,
            'academic_year_id' => $this->activeYear->id,
            'grade_id' => $section->grade_id,
        ]);

        if ($hours > 0) {
            $session = FieldSession::factory()->create([
                'user_id' => $this->teacher->id,
                'academic_year_id' => $this->activeYear->id,
                'base_hours' => 0,
                'start_datetime' => now()->subDays(7),
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
                'hours' => $hours,
            ]);
        }

        return $student;
    }

    // ============================================
    // TEST: Teacher sees only their students via HTTP
    // ============================================

    public function test_teacher_dashboard_includes_distribution_props(): void
    {
        $onTrack = $this->createStudentWithHours($this->sectionA, 90);
        $atRisk = $this->createStudentWithHours($this->sectionA, 30);

        $response = $this->actingAs($this->teacher)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('teacher/dashboard')
            ->has('totalStudents')
            ->has('distribution')
            ->has('onTrackStudents')
            ->has('atRiskStudents')
            ->has('studentsWithNoHours')
            ->has('outstandingStudents')
            ->has('topStudents')
        );
    }

    public function test_distribution_counts_match_calculated_values(): void
    {
        // On Track: 80h → ≥80%
        $this->createStudentWithHours($this->sectionA, 80);
        // In Progress: 50h → 40-79%
        $this->createStudentWithHours($this->sectionA, 50);
        // At Risk: 30h → <40%
        $this->createStudentWithHours($this->sectionA, 30);
        // Zero Hours: 0h
        $this->createStudentWithHours($this->sectionA, 0);

        $response = $this->actingAs($this->teacher)->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->where('totalStudents', 4)
            ->where('distribution.onTrack', 1)
            ->where('distribution.inProgress', 1)
            ->where('distribution.atRisk', 1)
            ->where('distribution.zeroHours', 1)
        );
    }

    public function test_at_risk_students_sorted_by_percentage_ascending(): void
    {
        $worst = $this->createStudentWithHours($this->sectionA, 10);  // 10%
        $better = $this->createStudentWithHours($this->sectionA, 30); // 30%

        $response = $this->actingAs($this->teacher)->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->has('atRiskStudents', 2)
            ->where('atRiskStudents.0.id', $worst->id)
            ->where('atRiskStudents.1.id', $better->id)
        );
    }

    public function test_distribution_handles_teacher_with_no_students(): void
    {
        // No students enrolled — teacher still has assignments though
        $response = $this->actingAs($this->teacher)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('teacher/dashboard')
            ->where('totalStudents', 0)
            ->where('distribution.onTrack', 0)
            ->where('distribution.inProgress', 0)
            ->where('distribution.atRisk', 0)
            ->where('distribution.zeroHours', 0)
            ->where('onTrackStudents', [])
            ->where('atRiskStudents', [])
            ->where('outstandingStudents', [])
            ->where('topStudents', [])
        );
    }

    public function test_distribution_spanning_multiple_sections(): void
    {
        $studentA = $this->createStudentWithHours($this->sectionA, 85);
        $studentB = $this->createStudentWithHours($this->sectionB, 25);

        $response = $this->actingAs($this->teacher)->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->where('totalStudents', 2)
            ->where('distribution.onTrack', 1)
            ->where('distribution.atRisk', 1)
            ->where('onTrackStudents.0.id', $studentA->id)
            ->where('atRiskStudents.0.id', $studentB->id)
        );
    }

    public function test_distribution_students_contain_section_and_grade_info(): void
    {
        $student = $this->createStudentWithHours($this->sectionA, 85);

        $response = $this->actingAs($this->teacher)->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->where('onTrackStudents.0.name', $student->name)
            ->where('onTrackStudents.0.sectionName', 'Sección A')
            ->where('onTrackStudents.0.gradeName', '5to Año')
            ->where('onTrackStudents.0.hours', 85)
            ->where('onTrackStudents.0.quota', 100)
            ->where('onTrackStudents.0.status', 'green')
        );
    }

    public function test_distribution_outstanding_students_meet_or_exceed_quota(): void
    {
        $overachiever = $this->createStudentWithHours($this->sectionA, 120); // 120%
        $exact100 = $this->createStudentWithHours($this->sectionA, 100);      // 100%

        $response = $this->actingAs($this->teacher)->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->has('outstandingStudents', 2)
            ->where('outstandingStudents.0.id', $overachiever->id)
            ->where('outstandingStudents.1.id', $exact100->id)
        );
    }
}
