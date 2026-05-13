<?php

namespace Tests\Feature\Dashboard;

use App\Models\AcademicYear;
use App\Models\FieldSession;
use App\Models\FieldSessionStatus;
use App\Models\Grade;
use App\Models\Section;
use App\Models\TeacherAssignment;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TeacherUpcomingSessionsTest extends TestCase
{
    use RefreshDatabase;

    protected AcademicYear $activeYear;

    protected User $teacher;

    protected Section $section;

    protected Grade $grade;

    protected FieldSessionStatus $plannedStatus;

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

        $this->grade = Grade::factory()->create(['name' => '5to Año']);

        $this->section = Section::factory()->create([
            'name' => 'Sección A',
            'grade_id' => $this->grade->id,
            'academic_year_id' => $this->activeYear->id,
        ]);

        $this->teacher = User::factory()->create(['name' => 'Prof. Test']);
        $this->teacher->assignRole('profesor');

        TeacherAssignment::create([
            'user_id' => $this->teacher->id,
            'section_id' => $this->section->id,
            'academic_year_id' => $this->activeYear->id,
            'grade_id' => $this->grade->id,
        ]);

        $this->plannedStatus = FieldSessionStatus::first()
            ?? FieldSessionStatus::factory()->create(['name' => 'planned']);
    }

    // ============================================
    // TEST: Upcoming sessions via HTTP
    // ============================================

    public function test_upcoming_sessions_prop_is_included(): void
    {
        $response = $this->actingAs($this->teacher)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('teacher/dashboard')
            ->has('upcomingSessions')
        );
    }

    public function test_upcoming_sessions_only_include_future_dates(): void
    {
        // Past session — should NOT appear
        FieldSession::factory()->create([
            'name' => 'Sesión Pasada',
            'user_id' => $this->teacher->id,
            'academic_year_id' => $this->activeYear->id,
            'start_datetime' => now()->subDays(10),
            'status_id' => $this->plannedStatus->id,
            'location_name' => 'Huerto',
        ]);

        // Future session — SHOULD appear
        $future = FieldSession::factory()->create([
            'name' => 'Sesión Futura',
            'user_id' => $this->teacher->id,
            'academic_year_id' => $this->activeYear->id,
            'start_datetime' => now()->addDays(5),
            'status_id' => $this->plannedStatus->id,
            'location_name' => 'Cancha',
        ]);

        $response = $this->actingAs($this->teacher)->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->has('upcomingSessions', 1)
            ->where('upcomingSessions.0.id', $future->id)
            ->where('upcomingSessions.0.name', 'Sesión Futura')
            ->where('upcomingSessions.0.location', 'Cancha')
        );
    }

    public function test_upcoming_sessions_ordered_by_date_ascending(): void
    {
        $later = FieldSession::factory()->create([
            'name' => 'Sesión Tardía',
            'user_id' => $this->teacher->id,
            'academic_year_id' => $this->activeYear->id,
            'start_datetime' => now()->addDays(10),
            'status_id' => $this->plannedStatus->id,
        ]);

        $sooner = FieldSession::factory()->create([
            'name' => 'Sesión Próxima',
            'user_id' => $this->teacher->id,
            'academic_year_id' => $this->activeYear->id,
            'start_datetime' => now()->addDays(2),
            'status_id' => $this->plannedStatus->id,
        ]);

        $response = $this->actingAs($this->teacher)->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->has('upcomingSessions', 2)
            ->where('upcomingSessions.0.id', $sooner->id)
            ->where('upcomingSessions.1.id', $later->id)
        );
    }

    public function test_upcoming_sessions_empty_when_no_future_sessions(): void
    {
        // Create only past sessions
        FieldSession::factory()->create([
            'name' => 'Pasada',
            'user_id' => $this->teacher->id,
            'academic_year_id' => $this->activeYear->id,
            'start_datetime' => now()->subDays(5),
            'status_id' => $this->plannedStatus->id,
        ]);

        $response = $this->actingAs($this->teacher)->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->where('upcomingSessions', [])
        );
    }

    public function test_upcoming_sessions_filters_by_academic_year(): void
    {
        $otherYear = AcademicYear::factory()->create([
            'is_active' => false,
            'required_hours' => 80,
            'name' => '2025',
        ]);

        // Session in other year
        FieldSession::factory()->create([
            'name' => 'Sesión Otro Año',
            'user_id' => $this->teacher->id,
            'academic_year_id' => $otherYear->id,
            'start_datetime' => now()->addDays(5),
            'status_id' => $this->plannedStatus->id,
        ]);

        // Query active year only
        $response = $this->actingAs($this->teacher)->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->where('upcomingSessions', [])
        );
    }

    public function test_upcoming_sessions_excludes_other_teachers(): void
    {
        $otherTeacher = User::factory()->create();
        $otherTeacher->assignRole('profesor');

        // Other teacher's session
        FieldSession::factory()->create([
            'name' => 'Sesión Otro Profesor',
            'user_id' => $otherTeacher->id,
            'academic_year_id' => $this->activeYear->id,
            'start_datetime' => now()->addDays(3),
            'status_id' => $this->plannedStatus->id,
        ]);

        // Teacher's own session
        $ownSession = FieldSession::factory()->create([
            'name' => 'Mi Sesión',
            'user_id' => $this->teacher->id,
            'academic_year_id' => $this->activeYear->id,
            'start_datetime' => now()->addDays(5),
            'status_id' => $this->plannedStatus->id,
        ]);

        $response = $this->actingAs($this->teacher)->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->has('upcomingSessions', 1)
            ->where('upcomingSessions.0.id', $ownSession->id)
        );
    }

    public function test_upcoming_sessions_include_status_and_section_names(): void
    {
        FieldSession::factory()->create([
            'name' => 'Sesión Planificada',
            'user_id' => $this->teacher->id,
            'academic_year_id' => $this->activeYear->id,
            'start_datetime' => now()->addDays(3),
            'status_id' => $this->plannedStatus->id,
            'location_name' => 'Comunidad',
        ]);

        $response = $this->actingAs($this->teacher)->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->has('upcomingSessions', 1)
            ->where('upcomingSessions.0.statusName', $this->plannedStatus->name)
            ->where('upcomingSessions.0.sectionName', fn ($v) => is_string($v))
            ->where('upcomingSessions.0.date', fn ($v) => is_string($v))
        );
    }
}
