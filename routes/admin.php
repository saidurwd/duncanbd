<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SecurityController;
use Illuminate\Support\Facades\Route;

// Prefix "config('admin.path')" and name "admin." are applied in bootstrap/app.php.
Route::middleware(['auth', 'admin.access'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/security', SecurityController::class)->name('security');
});
