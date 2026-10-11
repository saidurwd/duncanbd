<?php

use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\SeoController;
use Illuminate\Support\Facades\Route;

// Public, cookie-free, guest-cacheable routes (the "site" middleware group). Registered last so the
// catch-all page resolver never shadows real routes.
Route::middleware('site')->group(function () {
    Route::get('/', [PageController::class, 'home'])->name('home');
    Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
    Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

    Route::get('/{path}', [PageController::class, 'show'])
        ->where('path', '[a-z0-9\-/]+')
        ->name('page.show');
});
