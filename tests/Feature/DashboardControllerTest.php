<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
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
            ->has('globalCompliance')
            ->has('sectionRanking')
            ->has('termComparison')
            ->has('sessionStats')
            ->has('alerts')
            ->has('activityCategoryDistribution')
            ->has('locationDistribution')
            ->has('teacherWorkload')
            ->has('yearOverYear')
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
}
