<?php

use App\Http\Controllers\Admin\ContentResourceController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DocumentCategoryController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\PostController;
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

    Route::prefix('documents')->name('documents.')->middleware('permission:documents.investor.manage|documents.general.manage')->group(function () {
        Route::get('/', [DocumentController::class, 'index'])->name('index');
        Route::get('/create', [DocumentController::class, 'create'])->name('create');
        Route::post('/', [DocumentController::class, 'store'])->name('store');
        Route::get('/categories', [DocumentCategoryController::class, 'index'])->middleware('permission:documents.investor.manage')->name('categories.index');
        Route::post('/categories', [DocumentCategoryController::class, 'store'])->middleware('permission:documents.investor.manage')->name('categories.store');
        Route::put('/categories/{category}', [DocumentCategoryController::class, 'update'])->middleware('permission:documents.investor.manage')->name('categories.update');
        Route::delete('/categories/{category}', [DocumentCategoryController::class, 'destroy'])->middleware('permission:documents.investor.manage')->name('categories.destroy');
        Route::get('/{document}/edit', [DocumentController::class, 'edit'])->name('edit');
        Route::put('/{document}', [DocumentController::class, 'update'])->name('update');
        Route::post('/{document}/publish', [DocumentController::class, 'publish'])->name('publish');
        Route::post('/{document}/unpublish', [DocumentController::class, 'unpublish'])->name('unpublish');
        Route::get('/{document}/versions/{version}', [DocumentController::class, 'download'])->whereNumber('version')->name('download');
        Route::delete('/{document}', [DocumentController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('news')->name('posts.')->middleware('permission:news.edit')->group(function () {
        Route::get('/', [PostController::class, 'index'])->name('index');
        Route::get('/create', [PostController::class, 'create'])->name('create');
        Route::post('/', [PostController::class, 'store'])->name('store');
        Route::post('/categories', [PostController::class, 'storeCategory'])->name('categories.store');
        Route::get('/{post}/edit', [PostController::class, 'edit'])->name('edit');
        Route::put('/{post}', [PostController::class, 'update'])->name('update');
        Route::post('/{post}/publish', [PostController::class, 'publish'])->name('publish');
        Route::post('/{post}/unpublish', [PostController::class, 'unpublish'])->name('unpublish');
        Route::delete('/{post}', [PostController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('content/{resource}')->name('content.')->where(['resource' => 'businesses|estates|people|timeline'])
        ->middleware('permission:pages.publish')->group(function () {
            Route::get('/', [ContentResourceController::class, 'index'])->name('index');
            Route::get('/create', [ContentResourceController::class, 'create'])->name('create');
            Route::post('/', [ContentResourceController::class, 'store'])->name('store');
            Route::get('/{id}/edit', [ContentResourceController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::put('/{id}', [ContentResourceController::class, 'update'])->whereNumber('id')->name('update');
            Route::delete('/{id}', [ContentResourceController::class, 'destroy'])->whereNumber('id')->name('destroy');
        });
});
