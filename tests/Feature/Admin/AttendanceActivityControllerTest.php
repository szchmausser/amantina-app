<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\ActivityCategory;
use App\Models\Attendance;
use App\Models\AttendanceActivity;
use App\Models\FieldSession;
use App\Models\FieldSessionStatus;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AttendanceActivityControllerTest extends TestCase
{
    use RefreshDatabase;

    protected FieldSessionStatus $plannedStatus;

    protected AcademicYear $academicYear;

    protected User $admin;

    protected User $profesor;

    protected User $otherProfesor;

    protected ActivityCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(RoleAndPermissionSeeder::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->withoutVite();

        $this->plannedStatus = FieldSessionStatus::create(['name' => 'planned', 'description' => 'Planificada']);

        $this->academicYear = AcademicYear::factory()->create(['is_active' => true]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->profesor = User::factory()->create();
        $this->profesor->assignRole('profesor');

        $this->otherProfesor = User::factory()->create();
        $this->otherProfesor->assignRole('profesor');

        $this->category = ActivityCategory::factory()->create();
    }

    protected function createAttendanceForUser(User $sessionOwner): Attendance
    {
        $session = FieldSession::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'user_id' => $sessionOwner->id,
            'status_id' => $this->plannedStatus->id,
            'base_hours' => 4,
        ]);

        $student = User::factory()->create();
        $student->assignRole('alumno');

        return Attendance::create([
            'field_session_id' => $session->id,
            'user_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'attended' => true,
        ]);
    }

    public function test_admin_can_store_activity(): void
    {
        $attendance = $this->createAttendanceForUser($this->profesor);

        $response = $this->actingAs($this->admin)->post(
            route('admin.attendance-activities.store'),
            [
                'attendance_id' => $attendance->id,
                'activity_category_id' => $this->category->id,
                'hours' => 2.5,
                'notes' => 'Siembra de hortalizas',
            ]
        );

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('attendance_activities', [
            'attendance_id' => $attendance->id,
            'activity_category_id' => $this->category->id,
            'hours' => 2.5,
            'notes' => 'Siembra de hortalizas',
        ]);
    }

    public function test_profesor_can_store_activity_for_own_session(): void
    {
        $attendance = $this->createAttendanceForUser($this->profesor);

        $response = $this->actingAs($this->profesor)->post(
            route('admin.attendance-activities.store'),
            [
                'attendance_id' => $attendance->id,
                'activity_category_id' => $this->category->id,
                'hours' => 1.5,
            ]
        );

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('attendance_activities', [
            'attendance_id' => $attendance->id,
            'activity_category_id' => $this->category->id,
            'hours' => 1.5,
        ]);
    }

    public function test_profesor_cannot_store_activity_for_other_session(): void
    {
        $attendance = $this->createAttendanceForUser($this->otherProfesor);

        $response = $this->actingAs($this->profesor)->post(
            route('admin.attendance-activities.store'),
            [
                'attendance_id' => $attendance->id,
                'activity_category_id' => $this->category->id,
                'hours' => 1.5,
            ]
        );

        $response->assertStatus(403);
    }

    public function test_user_without_permission_cannot_store_activity(): void
    {
        $attendance = $this->createAttendanceForUser($this->profesor);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(
            route('admin.attendance-activities.store'),
            [
                'attendance_id' => $attendance->id,
                'activity_category_id' => $this->category->id,
                'hours' => 1.5,
            ]
        );

        $response->assertStatus(403);
    }

    public function test_admin_can_update_activity(): void
    {
        $attendance = $this->createAttendanceForUser($this->profesor);

        $activity = AttendanceActivity::create([
            'attendance_id' => $attendance->id,
            'activity_category_id' => $this->category->id,
            'hours' => 1,
        ]);

        $response = $this->actingAs($this->admin)->put(
            route('admin.attendance-activities.update', $activity),
            [
                'activity_category_id' => $this->category->id,
                'hours' => 3,
                'notes' => 'Actualizado',
            ]
        );

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('attendance_activities', [
            'id' => $activity->id,
            'hours' => 3,
            'notes' => 'Actualizado',
        ]);
    }

    public function test_profesor_can_update_activity_for_own_session(): void
    {
        $attendance = $this->createAttendanceForUser($this->profesor);

        $activity = AttendanceActivity::create([
            'attendance_id' => $attendance->id,
            'activity_category_id' => $this->category->id,
            'hours' => 1,
        ]);

        $response = $this->actingAs($this->profesor)->put(
            route('admin.attendance-activities.update', $activity),
            [
                'activity_category_id' => $this->category->id,
                'hours' => 2,
            ]
        );

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('attendance_activities', [
            'id' => $activity->id,
            'hours' => 2,
        ]);
    }

    public function test_profesor_cannot_update_activity_for_other_session(): void
    {
        $attendance = $this->createAttendanceForUser($this->otherProfesor);

        $activity = AttendanceActivity::create([
            'attendance_id' => $attendance->id,
            'activity_category_id' => $this->category->id,
            'hours' => 1,
        ]);

        $response = $this->actingAs($this->profesor)->put(
            route('admin.attendance-activities.update', $activity),
            [
                'activity_category_id' => $this->category->id,
                'hours' => 2,
            ]
        );

        $response->assertStatus(403);
    }

    public function test_admin_can_delete_activity(): void
    {
        $attendance = $this->createAttendanceForUser($this->profesor);

        $activity = AttendanceActivity::create([
            'attendance_id' => $attendance->id,
            'activity_category_id' => $this->category->id,
            'hours' => 1,
        ]);

        $response = $this->actingAs($this->admin)->delete(
            route('admin.attendance-activities.destroy', $activity),
        );

        $response->assertSessionHas('success');
        $this->assertSoftDeleted('attendance_activities', ['id' => $activity->id]);
    }

    public function test_user_without_permission_cannot_delete_activity(): void
    {
        $attendance = $this->createAttendanceForUser($this->profesor);

        $activity = AttendanceActivity::create([
            'attendance_id' => $attendance->id,
            'activity_category_id' => $this->category->id,
            'hours' => 1,
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->delete(
            route('admin.attendance-activities.destroy', $activity),
        );

        $response->assertStatus(403);
    }

    public function test_store_activity_shows_warning_when_exceeding_base_hours(): void
    {
        $attendance = $this->createAttendanceForUser($this->profesor);

        // First activity with 3 hours
        AttendanceActivity::create([
            'attendance_id' => $attendance->id,
            'activity_category_id' => $this->category->id,
            'hours' => 3,
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('admin.attendance-activities.store'),
            [
                'attendance_id' => $attendance->id,
                'activity_category_id' => $this->category->id,
                'hours' => 2,
            ]
        );

        $response->assertSessionHas('warning');
    }

    public function test_full_attendance_flow(): void
    {
        $session = FieldSession::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'user_id' => $this->profesor->id,
            'status_id' => $this->plannedStatus->id,
            'base_hours' => 4,
        ]);

        $student = User::factory()->create();
        $student->assignRole('alumno');

        $attendance = Attendance::create([
            'field_session_id' => $session->id,
            'user_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'attended' => true,
        ]);

        $response = $this->actingAs($this->profesor)->post(
            route('admin.attendance-activities.store'),
            [
                'attendance_id' => $attendance->id,
                'activity_category_id' => $this->category->id,
                'hours' => 2,
                'notes' => 'Desmalezado',
            ]
        );

        $response->assertSessionHasNoErrors();

        $category2 = ActivityCategory::factory()->create(['name' => 'Riego']);

        $response = $this->actingAs($this->profesor)->post(
            route('admin.attendance-activities.store'),
            [
                'attendance_id' => $attendance->id,
                'activity_category_id' => $category2->id,
                'hours' => 1.5,
                'notes' => 'Riego de plantación',
            ]
        );

        $response->assertSessionHasNoErrors();

        $totalHours = $attendance->attendanceActivities()->sum('hours');
        $this->assertEquals(3.5, $totalHours);
        $this->assertEquals(2, $attendance->attendanceActivities()->count());
    }
}
