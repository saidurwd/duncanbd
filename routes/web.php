<?php

use App\Http\Controllers\Site\PageController;
use Illuminate\Support\Facades\Route;

// Session-bearing public routes. Cacheable public pages live in routes/site.php.
Route::get('/preview/page/{page}', [PageController::class, 'preview'])
    ->middleware('signed')
    ->name('preview.page');
