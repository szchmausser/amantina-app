<?php

use App\Http\Controllers\Admin\AcademicStructureOverviewController;
use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\GradeController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RepresentativeController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SchoolTermController;
use App\Http\Controllers\Admin\SectionController;
use App\Http\Controllers\Admin\TeacherAssignmentController;
use App\Http\Controllers\Admin\TermTypeController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    // Admin Routes
    Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class);
        Route::resource('roles', RoleController::class)->only(['index', 'show', 'edit', 'update']);
        Route::get('permissions', [PermissionController::class, 'index'])->name('permissions.index');

        // Academic Structure (show redirects to index/academic year)
        Route::resource('academic-years', AcademicYearController::class);
        Route::resource('grades', GradeController::class)->except(['show']);
        Route::resource('sections', SectionController::class);
        Route::resource('school-terms', SchoolTermController::class)->except(['show']);
        Route::resource('term-types', TermTypeController::class)->except(['create', 'edit', 'show']);

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
    });
});

require __DIR__.'/settings.php';
