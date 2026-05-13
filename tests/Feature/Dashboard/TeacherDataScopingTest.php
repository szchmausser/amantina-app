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

class TeacherDataScopingTest extends TestCase
{
    use RefreshDatabase;

    protected AcademicYear $activeYear;

    protected User $teacherA;

    protected User $teacherB;

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

        $this->teacherA = User::factory()->create(['name' => 'Prof. A']);
        $this->teacherA->assignRole('profesor');

        $this->teacherB = User::factory()->create(['name' => 'Prof. B']);
        $this->teacherB->assignRole('profesor');

        // Teacher A → Section A, Teacher B → Section B
        TeacherAssignment::create([
            'user_id' => $this->teacherA->id,
            'section_id' => $this->sectionA->id,
            'academic_year_id' => $this->activeYear->id,
            'grade_id' => $this->grade->id,
        ]);

        TeacherAssignment::create([
            'user_id' => $this->teacherB->id,
            'section_id' => $this->sectionB->id,
            'academic_year_id' => $this->activeYear->id,
            'grade_id' => $this->grade->id,
        ]);
    }

    /**
     * Helper: Create an enrolled student with hours.
     */
    protected function createStudentWithHours(
        Section $section,
        float $hours,
        User $teacher,
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
                'user_id' => $teacher->id,
                'academic_year_id' => $this->activeYear->id,
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
    // TEST: Data scoping — teacher isolation
    // ============================================

    public function test_teacher_a_does_not_see_students_from_teacher_b_sections(): void
    {
        $studentA = $this->createStudentWithHours($this->sectionA, 85, $this->teacherA);
        $studentB = $this->createStudentWithHours($this->sectionB, 50, $this->teacherB);

        $response = $this->actingAs($this->teacherA)->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->where('totalStudents', 1)
        );

        // Extract props from the response to verify student IDs
        $props = $response->inertiaProps();
        $allStudentIds = $this->collectDistributionStudentIds($props);

        $this->assertContains($studentA->id, $allStudentIds);
        $this->assertNotContains($studentB->id, $allStudentIds);
    }

    public function test_teacher_with_no_assignments_sees_empty_arrays_no_errors(): void
    {
        $unassignedTeacher = User::factory()->create();
        $unassignedTeacher->assignRole('profesor');

        $response = $this->actingAs($unassignedTeacher)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('teacher/dashboard')
            ->where('totalStudents', 0)
            ->where('distribution.onTrack', 0)
            ->where('sections', [])
            ->where('onTrackStudents', [])
            ->where('atRiskStudents', [])
            ->where('outstandingStudents', [])
            ->where('topStudents', [])
            ->where('studentsWithNoHours', [])
            ->where('upcomingSessions', [])
        );
    }

    public function test_deleted_teacher_assignments_are_excluded(): void
    {
        $student = $this->createStudentWithHours($this->sectionA, 85, $this->teacherA);

        // Soft-delete Teacher A's assignment
        TeacherAssignment::where('user_id', $this->teacherA->id)
            ->where('section_id', $this->sectionA->id)
            ->delete();

        $response = $this->actingAs($this->teacherA)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('totalStudents', 0)
            ->where('sections', [])
            ->where('onTrackStudents', [])
        );

        // Student should NOT appear in teacher A's results after assignment deletion
        $props = $response->inertiaProps();
        $allStudentIds = $this->collectDistributionStudentIds($props);
        $this->assertNotContains($student->id, $allStudentIds);
    }

    public function test_teacher_only_sees_sections_they_are_assigned_to(): void
    {
        $this->createStudentWithHours($this->sectionA, 85, $this->teacherA);
        $this->createStudentWithHours($this->sectionB, 50, $this->teacherB);

        $response = $this->actingAs($this->teacherA)->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->has('sections', 1)
            ->where('sections.0.sectionName', 'Sección A')
        );
    }

    /**
     * Collect all student IDs from distribution arrays in the dashboard props.
     */
    private function collectDistributionStudentIds(array $props): array
    {
        $ids = [];
        foreach (['onTrackStudents', 'inProgressStudents', 'atRiskStudents', 'studentsWithNoHours'] as $key) {
            if (isset($props[$key])) {
                foreach ($props[$key] as $student) {
                    $ids[] = $student['id'];
                }
            }
        }

        return $ids;
    }
}
