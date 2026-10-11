<?php

use App\Http\Controllers\Site\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');

Route::get('/preview/page/{page}', [PageController::class, 'preview'])
    ->middleware('signed')
    ->name('preview.page');
