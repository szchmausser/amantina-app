<?php

use App\Http\Controllers\Admin\AcademicStructureOverviewController;
use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Admin\ActivityCategoryController;
use App\Http\Controllers\Admin\AttendanceActivityController;
use App\Http\Controllers\Admin\AttendanceController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\FieldSessionController;
use App\Http\Controllers\Admin\GradeController;
use App\Http\Controllers\Admin\HealthConditionController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RepresentativeController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SchoolTermController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\StudentHealthRecordController;
use App\Http\Controllers\Admin\StudentPdfController;
use App\Http\Controllers\Admin\TeacherAssignmentController;
use App\Http\Controllers\Admin\TermTypeController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, '__invoke'])->name('dashboard');

    // Admin Routes
    Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class);
        Route::get('users/{user}/pdf', StudentPdfController::class)->name('users.pdf');
        Route::resource('roles', RoleController::class)->only(['index', 'show', 'edit', 'update']);
        Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');

        // Academic Structure (show redirects to index/academic year)
        Route::resource('academic-years', AcademicYearController::class);
        Route::resource('grades', GradeController::class)->except(['show']);
        Route::resource('sections', SectionController::class);
        Route::resource('school-terms', SchoolTermController::class)->except(['show']);
        Route::resource('term-types', TermTypeController::class)->except(['create', 'edit', 'show']);
        Route::resource('health-conditions', HealthConditionController::class)->except(['create', 'edit', 'show']);
        Route::resource('activity-categories', ActivityCategoryController::class)->except(['create', 'edit', 'show']);
        Route::resource('locations', LocationController::class)->except(['create', 'edit', 'show']);

        // Field Sessions
        Route::resource('field-sessions', FieldSessionController::class);

        // Attendance
        Route::get('field-sessions/{field_session}/attendance', [AttendanceController::class, 'index'])->name('field-sessions.attendance');
        Route::post('field-sessions/{field_session}/attendance', [AttendanceController::class, 'store'])->name('field-sessions.attendance.store');
        Route::put('attendance/{attendance}', [AttendanceController::class, 'update'])->name('attendance.update');
        Route::delete('attendance/{attendance}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');
        Route::post('field-sessions/{field_session}/attendance/bulk-absent', [AttendanceController::class, 'bulkAbsent'])->name('field-sessions.attendance.bulk-absent');
        Route::post('field-sessions/{field_session}/attendance/bulk-assign-hours', [AttendanceController::class, 'bulkAssignHours'])->name('field-sessions.attendance.bulk-assign-hours');
        Route::post('field-sessions/{field_session}/attendance/quick-assign-hours', [AttendanceController::class, 'quickAssignHours'])->name('field-sessions.attendance.quick-assign-hours');

        // Attendance Activities
        Route::post('attendance-activities', [AttendanceActivityController::class, 'store'])->name('attendance-activities.store');
        Route::put('attendance-activities/{attendance_activity}', [AttendanceActivityController::class, 'update'])->name('attendance-activities.update');
        Route::delete('attendance-activities/{attendance_activity}', [AttendanceActivityController::class, 'destroy'])->name('attendance-activities.destroy');

        // Enrollments (promote routes BEFORE resource to avoid route conflicts)
        Route::get('enrollments/promote', [EnrollmentController::class, 'showPromotionPanel'])->name('enrollments.promote');
        Route::post('enrollments/promote', [EnrollmentController::class, 'promote'])->name('enrollments.promote.store');
        Route::resource('enrollments', EnrollmentController::class)->except(['show', 'edit', 'update']);

        // Teacher Assignments
        Route::resource('teacher-assignments', TeacherAssignmentController::class)->except(['show', 'edit', 'update']);

        // Academic Info Dashboard
        Route::get('academic-info', [AcademicStructureOverviewController::class, 'index'])->name('academic-info.index');

        // Student Representatives
        Route::post('student-representatives', [RepresentativeController::class, 'store'])->name('student-representatives.store');
        Route::delete('student-representatives/{student_representative}', [RepresentativeController::class, 'destroy'])->name('student-representatives.destroy');

        // Student Health Records
        Route::post('student-health-records', [StudentHealthRecordController::class, 'store'])->name('student-health-records.store');
        Route::put('student-health-records/{student_health_record}', [StudentHealthRecordController::class, 'update'])->name('student-health-records.update');
        Route::delete('student-health-records/{student_health_record}', [StudentHealthRecordController::class, 'destroy'])->name('student-health-records.destroy');
    });
});

require __DIR__.'/settings.php';
