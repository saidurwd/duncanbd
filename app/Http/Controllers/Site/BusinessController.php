<?php

namespace App\Http\Controllers\Site;

use App\Domain\Business\Models\Business;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class BusinessController extends Controller
{
    public function show(string $business): View
    {
        $model = Business::with(['logo.media', 'estates' => fn ($q) => $q->where('is_active', true)])
            ->where('is_active', true)->where('slug', $business)->firstOrFail();

        return view('site.business.show', ['business' => $model]);
    }

    public function estate(string $business, string $estate): View
    {
        $model = Business::where('is_active', true)->where('slug', $business)->firstOrFail();
        $record = $model->estates()->with('photo.media')->where('is_active', true)->where('slug', $estate)->firstOrFail();

        return view('site.business.estate', ['business' => $model, 'estate' => $record]);
    }
}
