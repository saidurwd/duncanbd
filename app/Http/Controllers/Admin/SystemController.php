<?php

namespace App\Http\Controllers\Admin;

use App\Domain\System\HealthCheck;
use App\Domain\System\SystemInfo;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SystemController extends Controller
{
    public function health(Request $request, HealthCheck $health): View
    {
        $checks = $health->run($request);

        return view('admin.system.health', [
            'groups' => collect($checks)->groupBy('group'),
            'summary' => HealthCheck::summary($checks),
            'ranAt' => now(),
        ]);
    }

    public function about(SystemInfo $info): View
    {
        return view('admin.system.about', ['application' => $info->application(), 'server' => $info->server(), 'packages' => $info->packages()]);
    }
}
