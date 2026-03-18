<?php

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
    });
});

require __DIR__.'/settings.php';
