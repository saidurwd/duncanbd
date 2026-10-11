<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SecurityController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('admin.security', [
            'user' => $user,
            'enabled' => $user->hasTwoFactorEnabled(),
            'pending' => $user->two_factor_secret !== null && ! $user->hasTwoFactorEnabled(),
        ]);
    }
}
