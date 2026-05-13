<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ActivityCategory;
use App\Models\Attendance;
use App\Models\AttendanceActivity;
use App\Models\Enrollment;
use App\Models\FieldSession;
use App\Models\Grade;
use App\Models\HealthCondition;
use App\Models\Section;
use App\Models\StudentHealthRecord;
use App\Models\TeacherAssignment;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected AcademicYear $activeYear;

    protected User $admin;

    protected User $profesor;

    protected User $alumno;

    protected User $representante;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->activeYear = AcademicYear::factory()->create([
            'is_active' => true,
            'required_hours' => 100,
        ]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->profesor = User::factory()->create();
        $this->profesor->assignRole('profesor');

        $this->alumno = User::factory()->create();
        $this->alumno->assignRole('alumno');

        $this->representante = User::factory()->create();
        $this->representante->assignRole('representante');
    }

    // ============================================
    // Authentication Tests
    // ============================================

    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_users_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');
        $response->assertStatus(200);
    }

    // ============================================
    // Authorization Tests
    // ============================================

    public function test_users_without_role_see_fallback_message(): void
    {
        $user = User::factory()->create();
        // User without any role

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('message', 'No tienes un rol asignado. Contacta al administrador.')
        );
    }

    public function test_users_with_role_can_access_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole('alumno');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }

    // ============================================
    // Role-Based Routing Tests
    // ============================================

    public function test_admin_sees_admin_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('admin/dashboard')
            ->has('totalStudents')
            ->has('requiredHours')
            ->has('averageHours')
            ->has('distribution')
            ->has('onTrackStudents')
            ->has('inProgressStudents')
            ->has('atRiskStudents')
            ->has('outstandingStudents')
            ->has('studentsWithNoHours')
            ->has('topSections')
            ->has('concerningSections')
            ->has('alerts')
        );
    }

    public function test_profesor_sees_teacher_dashboard(): void
    {
        $response = $this->actingAs($this->profesor)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('teacher/dashboard')
            ->has('sections')
            ->has('ownSessions')
            ->has('pendingAttendance')
            ->has('lowAttendanceStudents')
            ->has('categoryDistribution')
            ->has('sessionsPerTerm')
            ->has('healthReminders')
            ->has('totalStudents')
            ->has('distribution')
            ->has('onTrackStudents')
            ->has('inProgressStudents')
            ->has('atRiskStudents')
            ->has('outstandingStudents')
            ->has('topStudents')
            ->has('studentsWithNoHours')
            ->has('upcomingSessions')
        );
    }

    public function test_alumno_sees_student_dashboard(): void
    {
        $response = $this->actingAs($this->alumno)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('student/dashboard')
            ->has('progress')
            ->has('breakdownByYear')
            ->has('breakdownByTerm')
            ->has('sessionHistory')
            ->has('closureProjection')
            ->has('categoryParticipation')
            ->has('mostRecentSession')
            ->has('sectionAverage')
            ->has('evidenceCount')
        );
    }

    public function test_representante_sees_representative_dashboard(): void
    {
        $response = $this->actingAs($this->representante)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('representative/dashboard')
            ->has('studentName')
            ->has('studentId')
            ->has('progress')
            ->has('last4WeeksTrend')
            ->has('nextSession')
            ->has('healthReminder')
        );
    }

    // ============================================
    // Year Filter Tests
    // ============================================

    public function test_dashboard_filters_by_requested_year(): void
    {
        $previousYear = AcademicYear::factory()->create([
            'is_active' => false,
            'required_hours' => 80,
            'name' => '2025',
        ]);

        // Request specific year via query param
        $response = $this->actingAs($this->admin)->get('/dashboard?year='.$previousYear->id);

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('activeYear.id', $previousYear->id)
            ->where('activeYear.name', '2025')
        );
    }

    public function test_dashboard_uses_active_year_when_no_year_specified(): void
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('activeYear.id', $this->activeYear->id)
            ->where('activeYear.requiredHours', 100)
        );
    }

    public function test_dashboard_works_without_active_year(): void
    {
        // Deactivate all years
        AcademicYear::query()->update(['is_active' => false]);

        $response = $this->actingAs($this->admin)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('activeYear', null)
        );
    }

    // ============================================
    // Teacher Enhanced Alerts Tests (Task 4.3)
    // ============================================

    public function test_low_attendance_includes_total_hours_in_teacher_dashboard_response(): void
    {
        $grade = Grade::factory()->create(['academic_year_id' => $this->activeYear->id]);
        $section = Section::factory()->create([
            'grade_id' => $grade->id,
            'academic_year_id' => $this->activeYear->id,
        ]);

        TeacherAssignment::create([
            'user_id' => $this->profesor->id,
            'section_id' => $section->id,
            'academic_year_id' => $this->activeYear->id,
            'grade_id' => $grade->id,
        ]);

        $student = User::factory()->create();
        $student->assignRole('alumno');

        Enrollment::create([
            'user_id' => $student->id,
            'section_id' => $section->id,
            'academic_year_id' => $this->activeYear->id,
            'grade_id' => $grade->id,
        ]);

        // Create 2 attendances (below threshold of 3)
        for ($i = 0; $i < 2; $i++) {
            $session = FieldSession::factory()->create([
                'user_id' => $this->profesor->id,
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
                'hours' => 3.0,
            ]);
        }

        $response = $this->actingAs($this->profesor)->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->has('lowAttendanceStudents', 1)
            ->where('lowAttendanceStudents.0.studentId', $student->id)
            ->where('lowAttendanceStudents.0.attendanceCount', 2)
            ->where('lowAttendanceStudents.0.totalHours', 6)
        );
    }

    public function test_health_reminders_include_severity_in_teacher_dashboard_response(): void
    {
        $grade = Grade::factory()->create(['academic_year_id' => $this->activeYear->id]);
        $section = Section::factory()->create([
            'grade_id' => $grade->id,
            'academic_year_id' => $this->activeYear->id,
        ]);

        TeacherAssignment::create([
            'user_id' => $this->profesor->id,
            'section_id' => $section->id,
            'academic_year_id' => $this->activeYear->id,
            'grade_id' => $grade->id,
        ]);

        $student = User::factory()->create();
        $student->assignRole('alumno');

        Enrollment::create([
            'user_id' => $student->id,
            'section_id' => $section->id,
            'academic_year_id' => $this->activeYear->id,
            'grade_id' => $grade->id,
        ]);

        $condition = HealthCondition::factory()->create(['name' => 'Asma']);
        StudentHealthRecord::create([
            'user_id' => $student->id,
            'health_condition_id' => $condition->id,
            'received_by' => $this->profesor->id,
            'received_at' => now()->subDays(5),
        ]);

        $session = FieldSession::factory()->create([
            'user_id' => $this->profesor->id,
            'academic_year_id' => $this->activeYear->id,
            'start_datetime' => now()->subDays(3),
        ]);

        Attendance::create([
            'field_session_id' => $session->id,
            'user_id' => $student->id,
            'academic_year_id' => $this->activeYear->id,
            'attended' => true,
        ]);

        $response = $this->actingAs($this->profesor)->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->has('healthReminders', 1)
            ->where('healthReminders.0.studentName', $student->name)
            ->where('healthReminders.0.conditionName', 'Asma')
            ->where('healthReminders.0.severity', 'medium')
            ->where('healthReminders.0.daysSinceLastSession', fn ($v) => $v >= 3)
        );
    }

    public function test_category_distribution_includes_count_in_teacher_dashboard_response(): void
    {
        $grade = Grade::factory()->create(['academic_year_id' => $this->activeYear->id]);
        $section = Section::factory()->create([
            'grade_id' => $grade->id,
            'academic_year_id' => $this->activeYear->id,
        ]);

        TeacherAssignment::create([
            'user_id' => $this->profesor->id,
            'section_id' => $section->id,
            'academic_year_id' => $this->activeYear->id,
            'grade_id' => $grade->id,
        ]);

        $student = User::factory()->create();
        $student->assignRole('alumno');

        Enrollment::create([
            'user_id' => $student->id,
            'section_id' => $section->id,
            'academic_year_id' => $this->activeYear->id,
            'grade_id' => $grade->id,
        ]);

        $category = ActivityCategory::factory()->create(['name' => 'Siembra']);

        for ($i = 0; $i < 2; $i++) {
            $session = FieldSession::factory()->create([
                'user_id' => $this->profesor->id,
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
                'hours' => 2.5,
            ]);
        }

        $response = $this->actingAs($this->profesor)->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->has('categoryDistribution', 1)
            ->where('categoryDistribution.0.categoryName', 'Siembra')
            ->where('categoryDistribution.0.totalHours', 5)
            ->where('categoryDistribution.0.count', 2)
            ->where('categoryDistribution.0.minRequiredHours', null)
        );
    }
}
