<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Settings\Actions\UpdateSettings;
use App\Domain\Settings\SettingsRepository;
use App\Domain\Settings\SettingsSchema;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class SettingsController extends Controller
{
    public function edit(SettingsRepository $settings): View
    {
        return view('admin.settings.edit', [
            'groups' => SettingsSchema::groups(),
            'values' => $settings->all(),
        ]);
    }

    public function update(UpdateSettingsRequest $request, UpdateSettings $update): RedirectResponse
    {
        $update($request->validated());

        return redirect()->route('admin.settings.edit')->with('status', 'Settings saved.');
    }
}
