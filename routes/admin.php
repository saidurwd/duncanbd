<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

// Prefix "config('admin.path')" and name "admin." are applied in bootstrap/app.php.
Route::middleware(['auth', 'admin.access'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/security', SecurityController::class)->name('security');

    Route::middleware('permission:settings.manage')->group(function () {
        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    });

    Route::get('/media', [MediaController::class, 'index'])->middleware('permission:media.manage|media.view')->name('media.index');
    Route::middleware('permission:media.manage')->prefix('media')->name('media.')->group(function () {
        Route::post('/', [MediaController::class, 'store'])->name('store');
        Route::post('/folders', [MediaController::class, 'storeFolder'])->name('folders.store');
        Route::get('/{media}/edit', [MediaController::class, 'edit'])->name('edit');
        Route::put('/{media}', [MediaController::class, 'update'])->name('update');
        Route::delete('/{media}', [MediaController::class, 'destroy'])->name('destroy');
    });
});
