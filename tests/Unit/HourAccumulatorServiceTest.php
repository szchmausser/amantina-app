<?php

namespace Tests\Unit;

use App\Models\AcademicYear;
use App\Models\ActivityCategory;
use App\Models\Attendance;
use App\Models\AttendanceActivity;
use App\Models\FieldSession;
use App\Models\FieldSessionStatus;
use App\Models\Grade;
use App\Models\Section;
use App\Models\User;
use App\Services\HourAccumulatorService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class HourAccumulatorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected HourAccumulatorService $service;

    protected AcademicYear $activeYear;

    protected AcademicYear $previousYear;

    protected FieldSessionStatus $completedStatus;

    protected FieldSessionStatus $plannedStatus;

    protected FieldSessionStatus $cancelledStatus;

    protected User $student;

    protected Grade $grade;

    protected Section $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleAndPermissionSeeder::class);
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->service = new HourAccumulatorService;

        $this->activeYear = AcademicYear::factory()->create([
            'is_active' => true,
            'required_hours' => 100,
            'name' => '2026',
        ]);

        $this->previousYear = AcademicYear::factory()->create([
            'is_active' => false,
            'required_hours' => 80,
            'name' => '2025',
        ]);

        $this->completedStatus = FieldSessionStatus::create(['name' => 'completed', 'description' => 'Completada']);
        $this->plannedStatus = FieldSessionStatus::create(['name' => 'planned', 'description' => 'Planificada']);
        $this->cancelledStatus = FieldSessionStatus::create(['name' => 'cancelled', 'description' => 'Cancelada']);

        $this->grade = Grade::factory()->create();
        $this->section = Section::factory()->create(['grade_id' => $this->grade->id]);

        $this->student = User::factory()->create();
        $this->student->assignRole('alumno');
    }

    // ============================================
    // getStatusColor Tests - Traffic Light System
    // ============================================

    public function test_get_status_color_returns_green_when_hours_equals_quota(): void
    {
        $status = $this->service->getStatusColor(100, 100, 100);
        $this->assertEquals('green', $status);
    }

    public function test_get_status_color_returns_green_when_hours_exceeds_quota(): void
    {
        $status = $this->service->getStatusColor(120, 100, 120);
        $this->assertEquals('green', $status);
    }

    public function test_get_status_color_returns_green_when_percentage_above_80(): void
    {
        // 85% of quota
        $status = $this->service->getStatusColor(85, 100, 85);
        $this->assertEquals('green', $status);
    }

    public function test_get_status_color_returns_yellow_when_percentage_between_40_and_80(): void
    {
        // 50% of quota
        $status = $this->service->getStatusColor(50, 100, 50);
        $this->assertEquals('yellow', $status);
    }

    public function test_get_status_color_returns_yellow_at_80_percent(): void
    {
        // 80% is the boundary - >= 80 is green
        $status = $this->service->getStatusColor(80, 100, 80);
        $this->assertEquals('green', $status);
    }

    public function test_get_status_color_returns_yellow_at_40_percent(): void
    {
        // 40% is the boundary - >= 40 is yellow
        $status = $this->service->getStatusColor(40, 100, 40);
        $this->assertEquals('yellow', $status);
    }

    public function test_get_status_color_returns_red_when_percentage_below_40(): void
    {
        // 30% of quota
        $status = $this->service->getStatusColor(30, 100, 30);
        $this->assertEquals('red', $status);
    }

    public function test_get_status_color_returns_red_at_zero_hours(): void
    {
        $status = $this->service->getStatusColor(0, 100, 0);
        $this->assertEquals('red', $status);
    }

    // ============================================
    // getStudentTotalHours Tests
    // ============================================

    public function test_get_student_total_hours_returns_zero_when_no_attendance(): void
    {
        $result = $this->service->getStudentTotalHours($this->student->id);

        $this->assertEquals(0, $result['jornada_hours']);
        $this->assertEquals(0, $result['external_hours']);
        $this->assertEquals(0, $result['total_hours']);
        $this->assertEquals(100, $result['quota']);
        $this->assertEquals(0, $result['percentage']);
        $this->assertEquals('red', $result['status']);
    }

    public function test_get_student_total_hours_calculates_jornada_hours_correctly(): void
    {
        $profesor = User::factory()->create();
        $profesor->assignRole('profesor');

        $session = FieldSession::factory()->create([
            'academic_year_id' => $this->activeYear->id,
            'user_id' => $profesor->id,
            'status_id' => $this->completedStatus->id,
            'base_hours' => 4,
        ]);

        $attendance = Attendance::create([
            'field_session_id' => $session->id,
            'user_id' => $this->student->id,
            'academic_year_id' => $this->activeYear->id,
            'attended' => true,
        ]);

        AttendanceActivity::create([
            'attendance_id' => $attendance->id,
            'activity_category_id' => ActivityCategory::factory()->create()->id,
            'hours' => 4,
        ]);

        $result = $this->service->getStudentTotalHours($this->student->id);

        $this->assertEquals(4, $result['jornada_hours']);
        $this->assertEquals(0, $result['external_hours']);
        $this->assertEquals(4, $result['total_hours']);
        $this->assertEquals(100, $result['quota']);
        $this->assertEquals(4, $result['percentage']);
        $this->assertEquals('red', $result['status']);
    }

    public function test_get_student_total_hours_ignores_not_attended(): void
    {
        $profesor = User::factory()->create();
        $profesor->assignRole('profesor');

        $session = FieldSession::factory()->create([
            'academic_year_id' => $this->activeYear->id,
            'user_id' => $profesor->id,
            'status_id' => $this->completedStatus->id,
        ]);

        $attendance = Attendance::create([
            'field_session_id' => $session->id,
            'user_id' => $this->student->id,
            'academic_year_id' => $this->activeYear->id,
            'attended' => false, // Not attended
        ]);

        AttendanceActivity::create([
            'attendance_id' => $attendance->id,
            'activity_category_id' => ActivityCategory::factory()->create()->id,
            'hours' => 4,
        ]);

        $result = $this->service->getStudentTotalHours($this->student->id);

        $this->assertEquals(0, $result['jornada_hours']);
        $this->assertEquals(0, $result['total_hours']);
    }

    public function test_get_student_total_hours_filters_by_academic_year(): void
    {
        $profesor = User::factory()->create();
        $profesor->assignRole('profesor');

        // Session in current year
        $session1 = FieldSession::factory()->create([
            'academic_year_id' => $this->activeYear->id,
            'user_id' => $profesor->id,
            'status_id' => $this->completedStatus->id,
        ]);

        $attendance1 = Attendance::create([
            'field_session_id' => $session1->id,
            'user_id' => $this->student->id,
            'academic_year_id' => $this->activeYear->id,
            'attended' => true,
        ]);

        AttendanceActivity::create([
            'attendance_id' => $attendance1->id,
            'hours' => 30,
        ]);

        // Session in previous year
        $session2 = FieldSession::factory()->create([
            'academic_year_id' => $this->previousYear->id,
            'user_id' => $profesor->id,
            'status_id' => $this->completedStatus->id,
        ]);

        $attendance2 = Attendance::create([
            'field_session_id' => $session2->id,
            'user_id' => $this->student->id,
            'academic_year_id' => $this->previousYear->id,
            'attended' => true,
        ]);

        AttendanceActivity::create([
            'attendance_id' => $attendance2->id,
            'hours' => 50,
        ]);

        // Without year filter - defaults to active year (by design)
        $resultAll = $this->service->getStudentTotalHours($this->student->id);
        $this->assertEquals(30, $resultAll['jornada_hours']);

        // With year filter - should only return that year
        $resultCurrent = $this->service->getStudentTotalHours($this->student->id, $this->activeYear->id);
        $this->assertEquals(30, $resultCurrent['jornada_hours']);

        // With previous year filter
        $resultPrevious = $this->service->getStudentTotalHours($this->student->id, $this->previousYear->id);
        $this->assertEquals(50, $resultPrevious['jornada_hours']);
    }

    public function test_get_student_total_hours_respects_quota(): void
    {
        $profesor = User::factory()->create();
        $profesor->assignRole('profesor');

        $session = FieldSession::factory()->create([
            'academic_year_id' => $this->activeYear->id,
            'user_id' => $profesor->id,
            'status_id' => $this->completedStatus->id,
        ]);

        $attendance = Attendance::create([
            'field_session_id' => $session->id,
            'user_id' => $this->student->id,
            'academic_year_id' => $this->activeYear->id,
            'attended' => true,
        ]);

        AttendanceActivity::create([
            'attendance_id' => $attendance->id,
            'hours' => 85,
        ]);

        $result = $this->service->getStudentTotalHours($this->student->id);

        $this->assertEquals(85, $result['jornada_hours']);
        $this->assertEquals(85, $result['percentage']);
        $this->assertEquals('green', $result['status']);
    }

    public function test_get_student_total_hours_calculates_status_correctly(): void
    {
        $profesor = User::factory()->create();
        $profesor->assignRole('profesor');

        // Helper to create attendance with hours
        $createAttendance = function (int $hours, ?int $yearId = null) use ($profesor) {
            $session = FieldSession::factory()->create([
                'academic_year_id' => $yearId ?? $this->activeYear->id,
                'user_id' => $profesor->id,
                'status_id' => $this->completedStatus->id,
            ]);

            $attendance = Attendance::create([
                'field_session_id' => $session->id,
                'user_id' => $this->student->id,
                'academic_year_id' => $yearId ?? $this->activeYear->id,
                'attended' => true,
            ]);

            AttendanceActivity::create([
                'attendance_id' => $attendance->id,
                'hours' => $hours,
            ]);

            return $attendance;
        };

        // Test green (>= 80%) - quota is 100, so 85 hours = 85%
        $createAttendance(85);
        $result1 = $this->service->getStudentTotalHours($this->student->id);
        $this->assertEquals('green', $result1['status']);

        // Clean up for next test
        Attendance::query()->delete();
        AttendanceActivity::query()->delete();
        FieldSession::query()->delete();

        // Test yellow (40-79%) - 50 hours = 50%
        $createAttendance(50);
        $result2 = $this->service->getStudentTotalHours($this->student->id);
        $this->assertEquals('yellow', $result2['status']);

        // Clean up for next test
        Attendance::query()->delete();
        AttendanceActivity::query()->delete();
        FieldSession::query()->delete();

        // Test red (< 40%) - 20 hours = 20%
        $createAttendance(20);
        $result3 = $this->service->getStudentTotalHours($this->student->id);
        $this->assertEquals('red', $result3['status']);
    }

    public function test_get_student_total_hours_ignores_soft_deleted_attendance(): void
    {
        $profesor = User::factory()->create();
        $profesor->assignRole('profesor');

        $session = FieldSession::factory()->create([
            'academic_year_id' => $this->activeYear->id,
            'user_id' => $profesor->id,
            'status_id' => $this->completedStatus->id,
        ]);

        $attendance = Attendance::create([
            'field_session_id' => $session->id,
            'user_id' => $this->student->id,
            'academic_year_id' => $this->activeYear->id,
            'attended' => true,
        ]);

        AttendanceActivity::create([
            'attendance_id' => $attendance->id,
            'hours' => 4,
        ]);

        // Soft delete the attendance
        $attendance->delete();

        $result = $this->service->getStudentTotalHours($this->student->id);

        $this->assertEquals(0, $result['jornada_hours']);
    }

    // ============================================
    // Additive Design Tests (external_hours = 0)
    // ============================================

    public function test_external_hours_is_zero_until_hito_12(): void
    {
        // This test documents the current behavior
        // When Hito 12 is implemented, this should change
        $result = $this->service->getStudentTotalHours($this->student->id);

        $this->assertEquals(0, $result['external_hours']);
        $this->assertEquals(
            $result['jornada_hours'],
            $result['total_hours'],
            'Total hours should equal jornada hours when external hours is 0'
        );
    }

    // ============================================
    // Edge Cases
    // ============================================

    public function test_get_student_total_hours_returns_zero_quota_when_no_year(): void
    {
        // When year is null and no active year exists
        AcademicYear::query()->update(['is_active' => false]);

        $result = $this->service->getStudentTotalHours($this->student->id);

        $this->assertEquals(0, $result['quota']);
        $this->assertEquals('red', $result['status']);
    }

    public function test_get_student_total_hours_rounds_to_two_decimals(): void
    {
        $profesor = User::factory()->create();
        $profesor->assignRole('profesor');

        $session = FieldSession::factory()->create([
            'academic_year_id' => $this->activeYear->id,
            'user_id' => $profesor->id,
            'status_id' => $this->completedStatus->id,
        ]);

        $attendance = Attendance::create([
            'field_session_id' => $session->id,
            'user_id' => $this->student->id,
            'academic_year_id' => $this->activeYear->id,
            'attended' => true,
        ]);

        AttendanceActivity::create([
            'attendance_id' => $attendance->id,
            'hours' => 33.333,
        ]);

        $result = $this->service->getStudentTotalHours($this->student->id);

        $this->assertEquals(33.33, $result['jornada_hours']);
        $this->assertEquals(33.33, $result['total_hours']);
    }
}
