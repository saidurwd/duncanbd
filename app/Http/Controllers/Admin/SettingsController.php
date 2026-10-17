<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Settings\Actions\UpdateBranding;
use App\Domain\Settings\Actions\UpdateSettings;
use App\Domain\Settings\SettingsRepository;
use App\Domain\Settings\SettingsSchema;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBrandingRequest;
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
            'branding' => ['slots' => UpdateBranding::SLOTS, 'current' => $settings->all()['branding'] ?? []],
        ]);
    }

    public function branding(UpdateBrandingRequest $request, UpdateBranding $update): RedirectResponse
    {
        $update(
            ['logo' => $request->file('logo'), 'logo_dark' => $request->file('logo_dark'), 'favicon' => $request->file('favicon'), 'share_image' => $request->file('share_image')],
            (array) $request->input('remove', []),
            $request->user()->id,
        );

        return redirect()->route('admin.settings.edit')->with('status', 'Branding updated.');
    }

    public function update(UpdateSettingsRequest $request, UpdateSettings $update): RedirectResponse
    {
        $update($request->validated());

        return redirect()->route('admin.settings.edit')->with('status', 'Settings saved.');
    }
}
