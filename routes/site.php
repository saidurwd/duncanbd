<?php

use App\Http\Controllers\Site\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/{path}', [PageController::class, 'show'])
    ->where('path', '[a-z0-9\-/]+')
    ->name('page.show');
