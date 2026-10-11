<?php

use App\Http\Controllers\Site\CareersController;
use App\Http\Controllers\Site\ContactController;
use App\Http\Controllers\Site\NewsletterController;
use App\Http\Controllers\Site\PageController;
use Illuminate\Support\Facades\Route;

// Session-bearing public routes. Cacheable public pages live in routes/site.php.
Route::get('/preview/page/{page}', [PageController::class, 'preview'])
    ->middleware('signed')
    ->name('preview.page');

// Forms need a session (CSRF) so they live here, uncached, rather than in the cookie-free site group.
Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware(['throttle:forms', 'spam'])->name('contact.store');

Route::get('/careers/vacancies/{slug}/apply', [CareersController::class, 'applyForm'])->where('slug', '[a-z0-9\-]+')->name('careers.apply');
Route::post('/careers/vacancies/{slug}/apply', [CareersController::class, 'apply'])->where('slug', '[a-z0-9\-]+')->middleware(['throttle:forms', 'spam'])->name('careers.apply.store');

Route::get('/careers/vacancies/{slug}/applied', [CareersController::class, 'applied'])->where('slug', '[a-z0-9\-]+')->name('careers.applied');

Route::get('/forms/token', [NewsletterController::class, 'token'])->middleware('throttle:60,1')->name('forms.token');
Route::get('/newsletter', [NewsletterController::class, 'show'])->name('newsletter');
Route::get('/newsletter/thanks', [NewsletterController::class, 'thanks'])->name('newsletter.thanks');
Route::post('/newsletter', [NewsletterController::class, 'subscribe'])->middleware(['throttle:forms', 'spam'])->name('newsletter.subscribe');
Route::middleware('signed')->group(function () {
    Route::get('/newsletter/confirm/{subscriber}', [NewsletterController::class, 'confirmPage'])->name('newsletter.confirm');
    Route::post('/newsletter/confirm/{subscriber}', [NewsletterController::class, 'confirm'])->name('newsletter.confirm.store');
    Route::get('/newsletter/unsubscribe/{subscriber}', [NewsletterController::class, 'unsubscribePage'])->name('newsletter.unsubscribe');
    Route::post('/newsletter/unsubscribe/{subscriber}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe.store');
});
