<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Engagement\PersonalData;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Data-subject tools: look up, export and erase everything held about an email address. */
class PrivacyController extends Controller
{
    public function index(Request $request, PersonalData $personalData): View
    {
        $email = trim((string) $request->query('email'));

        return view('admin.privacy.index', [
            'email' => $email,
            'counts' => filter_var($email, FILTER_VALIDATE_EMAIL) ? $personalData->counts($email) : null,
        ]);
    }

    public function export(Request $request, PersonalData $personalData): Response
    {
        $data = $request->validate(['email' => ['required', 'email']]);

        activity('privacy')->causedBy($request->user())->withProperties(['email_hash' => sha1(strtolower($data['email']))])->log('personal data exported');

        return response()->json($personalData->export($data['email']), 200, [
            'Content-Disposition' => 'attachment; filename="personal-data.json"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function erase(Request $request, PersonalData $personalData): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email'], 'confirm' => ['required', 'in:ERASE']]);

        $personalData->erase($data['email']);
        activity('privacy')->causedBy($request->user())->withProperties(['email_hash' => sha1(strtolower($data['email']))])->log('personal data erased');

        return redirect()->route('admin.privacy.index')->with('status', 'All data for that address has been erased.');
    }
}
