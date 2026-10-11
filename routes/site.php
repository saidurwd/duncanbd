<?php

use App\Http\Controllers\Site\BusinessController;
use App\Http\Controllers\Site\CareersController;
use App\Http\Controllers\Site\DocumentController;
use App\Http\Controllers\Site\NewsController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\SearchController;
use App\Http\Controllers\Site\SeoController;
use Illuminate\Support\Facades\Route;
use Spatie\ResponseCache\Middlewares\CacheResponse;

// Public, cookie-free, guest-cacheable routes (the "site" middleware group). Registered last so the
// catch-all page resolver never shadows real routes.
Route::middleware('site')->group(function () {
    Route::get('/', [PageController::class, 'home'])->name('home');
    Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
    Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('/documents/{category}', [DocumentController::class, 'index'])->where('category', '[a-z0-9\-]+')->name('documents.category');
    Route::get('/documents/{category}/{slug}', [DocumentController::class, 'show'])->where(['category' => '[a-z0-9\-]+', 'slug' => '[a-z0-9\-]+'])->name('documents.show');
    // Search results vary per query and are cheap to abuse: never cached, rate limited.
    Route::get('/search', [SearchController::class, 'search'])->middleware('throttle:30,1')->withoutMiddleware(CacheResponse::class)->name('search');
    Route::get('/sitemap', [SearchController::class, 'sitemap'])->name('sitemap.html');

    Route::get('/careers/vacancies', [CareersController::class, 'index'])->name('careers.index');
    Route::get('/careers/vacancies/{slug}', [CareersController::class, 'show'])->where('slug', '[a-z0-9\-]+')->name('careers.show');

    Route::get('/news', [NewsController::class, 'index'])->name('news.index');
    Route::get('/news/{year}/{slug}', [NewsController::class, 'show'])->whereNumber('year')->where('slug', '[a-z0-9\-]+')->name('news.show');

    Route::get('/what-we-do/{business}', [BusinessController::class, 'show'])->where('business', '[a-z0-9\-]+')->name('business.show');
    Route::get('/what-we-do/{business}/estates/{estate}', [BusinessController::class, 'estate'])->where(['business' => '[a-z0-9\-]+', 'estate' => '[a-z0-9\-]+'])->name('business.estate');

    // Downloads must hit the app every time (counter, expiry); never cache them.
    Route::get('/download/{uuid}', [DocumentController::class, 'download'])->whereUuid('uuid')->withoutMiddleware(CacheResponse::class)->name('documents.download');

    Route::get('/{path}', [PageController::class, 'show'])
        ->where('path', '[a-z0-9\-/]+')
        ->name('page.show');
});
