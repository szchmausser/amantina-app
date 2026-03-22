<?php

use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Admin\GradeController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SchoolTermController;
use App\Http\Controllers\Admin\SectionController;
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
        Route::resource('sections', SectionController::class)->except(['show']);
        Route::resource('school-terms', SchoolTermController::class)->except(['show']);
    });
});

require __DIR__.'/settings.php';
