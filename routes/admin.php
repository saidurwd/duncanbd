<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\RedirectController;
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

    Route::prefix('pages')->name('pages.')->middleware('permission:pages.edit')->group(function () {
        Route::get('/', [PageController::class, 'index'])->name('index');
        Route::get('/create', [PageController::class, 'create'])->name('create');
        Route::post('/', [PageController::class, 'store'])->name('store');
        Route::get('/{page}/edit', [PageController::class, 'edit'])->name('edit');
        Route::put('/{page}', [PageController::class, 'update'])->name('update');
        Route::post('/{page}/transition', [PageController::class, 'transition'])->name('transition');
        Route::get('/{page}/preview', [PageController::class, 'preview'])->name('preview');
        Route::get('/{page}/revisions', [PageController::class, 'revisions'])->name('revisions');
        Route::post('/{page}/revisions/{revision}/restore', [PageController::class, 'restore'])->name('restore');
        Route::delete('/{page}', [PageController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('menus')->name('menus.')->middleware('permission:menus.manage')->group(function () {
        Route::get('/', [MenuController::class, 'index'])->name('index');
        Route::get('/{location}', [MenuController::class, 'edit'])->name('edit');
        Route::put('/{location}', [MenuController::class, 'update'])->name('update');
    });

    Route::prefix('redirects')->name('redirects.')->middleware('permission:seo.manage')->group(function () {
        Route::get('/', [RedirectController::class, 'index'])->name('index');
        Route::post('/', [RedirectController::class, 'store'])->name('store');
        Route::delete('/not-found', [RedirectController::class, 'clearNotFound'])->name('not-found.clear');
        Route::delete('/{redirect}', [RedirectController::class, 'destroy'])->name('destroy');
    });
});
