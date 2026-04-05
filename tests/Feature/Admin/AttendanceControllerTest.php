<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\ActivityCategory;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\FieldSession;
use App\Models\FieldSessionStatus;
use App\Models\Grade;
use App\Models\Section;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AttendanceControllerTest extends TestCase
{
    use RefreshDatabase;

    protected FieldSessionStatus $plannedStatus;

    protected AcademicYear $academicYear;

    protected User $admin;

    protected User $profesor;

    protected Grade $grade;

    protected Section $section;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $this->seed(RoleAndPermissionSeeder::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->withoutVite();

        // Create status
        $this->plannedStatus = FieldSessionStatus::create(['name' => 'planned', 'description' => 'Planificada']);

        // Create academic year
        $this->academicYear = AcademicYear::factory()->create(['is_active' => true]);

        // Create users
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->profesor = User::factory()->create();
        $this->profesor->assignRole('profesor');

        // Create grade and section
        $this->grade = Grade::factory()->create(['name' => '1er Año', 'order' => 1]);
        $this->section = Section::factory()->create(['name' => 'A', 'grade_id' => $this->grade->id]);
    }

    public function test_admin_can_view_attendance_page(): void
    {
        $session = FieldSession::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'user_id' => $this->profesor->id,
            'status_id' => $this->plannedStatus->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.field-sessions.attendance', $session));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('admin/attendances/index')
            ->has('fieldSession')
            ->has('groupedStudents')
            ->has('activityCategories')
        );
    }

    public function test_profesor_can_view_attendance_page(): void
    {
        $session = FieldSession::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'user_id' => $this->profesor->id,
            'status_id' => $this->plannedStatus->id,
        ]);

        $response = $this->actingAs($this->profesor)->get(route('admin.field-sessions.attendance', $session));

        $response->assertStatus(200);
    }

    public function test_user_without_permission_cannot_access_attendance(): void
    {
        $user = User::factory()->create();
        $session = FieldSession::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'user_id' => $this->profesor->id,
            'status_id' => $this->plannedStatus->id,
        ]);

        $response = $this->actingAs($user)->get(route('admin.field-sessions.attendance', $session));
        $response->assertStatus(403);
    }

    public function test_can_register_single_student_attendance(): void
    {
        $student = User::factory()->create();
        $student->assignRole('alumno');
        $student->enrollments()->create([
            'academic_year_id' => $this->academicYear->id,
            'grade_id' => $this->grade->id,
            'section_id' => $this->section->id,
        ]);

        $session = FieldSession::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'user_id' => $this->profesor->id,
            'status_id' => $this->plannedStatus->id,
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('admin.field-sessions.attendance.store', $session),
            [
                'field_session_id' => $session->id,
                'user_id' => $student->id,
                'attended' => true,
                'notes' => 'Asistió correctamente',
            ]
        );

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('attendances', [
            'field_session_id' => $session->id,
            'user_id' => $student->id,
            'attended' => true,
            'academic_year_id' => $this->academicYear->id,
        ]);
    }

    public function test_can_register_bulk_students_attendance(): void
    {
        $students = User::factory()->count(3)->create();
        foreach ($students as $student) {
            $student->assignRole('alumno');
            $student->enrollments()->create([
                'academic_year_id' => $this->academicYear->id,
                'grade_id' => $this->grade->id,
                'section_id' => $this->section->id,
            ]);
        }

        $session = FieldSession::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'user_id' => $this->profesor->id,
            'status_id' => $this->plannedStatus->id,
        ]);

        $response = $this->actingAs($this->admin)->post(
            route('admin.field-sessions.attendance.store', $session),
            [
                'field_session_id' => $session->id,
                'student_ids' => $students->pluck('id')->toArray(),
                'attended' => true,
            ]
        );

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('attendances', 3);
    }

    public function test_can_mark_students_as_absent(): void
    {
        $student = User::factory()->create();
        $student->assignRole('alumno');
        $student->enrollments()->create([
            'academic_year_id' => $this->academicYear->id,
            'grade_id' => $this->grade->id,
            'section_id' => $this->section->id,
        ]);

        $session = FieldSession::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'user_id' => $this->profesor->id,
            'status_id' => $this->plannedStatus->id,
        ]);

        // First register attendance
        Attendance::create([
            'field_session_id' => $session->id,
            'user_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'attended' => true,
        ]);

        // Mark as absent
        $response = $this->actingAs($this->admin)->post(
            route('admin.field-sessions.attendance.bulk-absent', $session),
            [
                'student_ids' => [$student->id],
            ]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('attendances', [
            'field_session_id' => $session->id,
            'user_id' => $student->id,
            'attended' => false,
        ]);
    }

    public function test_can_update_attendance(): void
    {
        $session = FieldSession::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'user_id' => $this->profesor->id,
            'status_id' => $this->plannedStatus->id,
        ]);

        $student = User::factory()->create();
        $student->assignRole('alumno');

        $attendance = Attendance::create([
            'field_session_id' => $session->id,
            'user_id' => $student->id,
            'academic_year_id' => $this->academicYear->id,
            'attended' => true,
            'notes' => 'Notas originales',
        ]);

        $response = $this->actingAs($this->admin)->put(
            route('admin.attendance.update', $attendance),
            [
                'attended' => false,
                'notes' => 'Notas actualizadas',
            ]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'attended' => false,
            'notes' => 'Notas actualizadas',
        ]);
    }

    public function test_can_bulk_assign_hours(): void
    {
        $category = ActivityCategory::factory()->create();

        $session = FieldSession::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'user_id' => $this->profesor->id,
            'status_id' => $this->plannedStatus->id,
        ]);

        $student = User::factory()->create();
        $student->assignRole('alumno');

        $response = $this->actingAs($this->admin)->post(
            route('admin.field-sessions.attendance.bulk-assign-hours', $session),
            [
                'data' => [
                    [
                        'user_id' => $student->id,
                        'activity_category_id' => $category->id,
                        'hours' => 2.5,
                        'notes' => 'Actividad realizada',
                    ],
                ],
            ]
        );

        $response->assertRedirect();

        // Should create attendance with activities
        $attendance = Attendance::where('field_session_id', $session->id)
            ->where('user_id', $student->id)
            ->first();

        $this->assertNotNull($attendance);
        $this->assertTrue($attendance->attended);
        $this->assertDatabaseHas('attendance_activities', [
            'attendance_id' => $attendance->id,
            'activity_category_id' => $category->id,
            'hours' => 2.5,
        ]);
    }

    public function test_can_quick_assign_hours(): void
    {
        $category = ActivityCategory::factory()->create();

        $session = FieldSession::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'user_id' => $this->profesor->id,
            'status_id' => $this->plannedStatus->id,
        ]);

        $student = User::factory()->create();
        $student->assignRole('alumno');

        $response = $this->actingAs($this->admin)->post(
            route('admin.field-sessions.attendance.quick-assign-hours', $session),
            [
                'user_id' => $student->id,
                'hours' => 3.0,
                'activity_category_id' => $category->id,
            ]
        );

        $response->assertRedirect();

        // Should create attendance with activities
        $attendance = Attendance::where('field_session_id', $session->id)
            ->where('user_id', $student->id)
            ->first();

        $this->assertNotNull($attendance);
        $this->assertTrue($attendance->attended);
        $this->assertDatabaseHas('attendance_activities', [
            'attendance_id' => $attendance->id,
            'activity_category_id' => $category->id,
            'hours' => 3.0,
        ]);
    }

    public function test_enrolled_students_appear_in_attendance_page(): void
    {
        // Create enrolled students
        $enrolledStudents = User::factory()->count(2)->create();
        foreach ($enrolledStudents as $student) {
            $student->assignRole('alumno');
            Enrollment::create([
                'user_id' => $student->id,
                'academic_year_id' => $this->academicYear->id,
                'grade_id' => $this->grade->id,
                'section_id' => $this->section->id,
            ]);
        }

        $session = FieldSession::factory()->create([
            'academic_year_id' => $this->academicYear->id,
            'user_id' => $this->profesor->id,
            'status_id' => $this->plannedStatus->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.field-sessions.attendance', $session));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->has('groupedStudents', 1)
        );
    }
}
