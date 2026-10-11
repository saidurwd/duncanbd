<?php

namespace App\Http\Controllers\Site;

use App\Domain\Careers\Models\Vacancy;
use App\Domain\Documents\Enums\DocumentStatus;
use App\Domain\Engagement\EnquiryRouter;
use App\Http\Controllers\Controller;
use App\Mail\ApplicationReceived;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CareersController extends Controller
{
    public function index(): View
    {
        return view('site.careers.index', ['vacancies' => Vacancy::open()->orderBy('closing_date')->orderBy('title')->get()]);
    }

    public function show(string $slug): View
    {
        // Published-but-closed vacancies stay visible (with a notice) so shared links don't 404 mid-campaign.
        $vacancy = Vacancy::where('status', DocumentStatus::Published->value)->where('slug', $slug)->firstOrFail();

        return view('site.careers.show', ['vacancy' => $vacancy, 'open' => $vacancy->isOpen()]);
    }

    /** Separate, uncached page: it needs a session for CSRF and a fresh time-trap token. */
    public function applyForm(string $slug): View
    {
        $vacancy = $this->openVacancy($slug);

        return view('site.careers.apply', ['vacancy' => $vacancy]);
    }

    public function apply(Request $request, string $slug, EnquiryRouter $router): RedirectResponse
    {
        $vacancy = $this->openVacancy($slug);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+()\-\s.]+$/'],
            'cover_letter' => ['nullable', 'string', 'max:5000'],
            'cv' => ['required', 'file', 'max:5120', 'extensions:pdf,doc,docx', 'mimes:pdf,doc,docx'],
            'consent' => ['accepted'],
        ]);

        $file = $request->file('cv');
        $path = $file->storeAs('careers/'.now()->format('Y/m'), Str::uuid().'.'.strtolower($file->guessExtension() ?: $file->getClientOriginalExtension()), 'local');

        $application = $vacancy->applications()->create([
            'name' => preg_replace('/\s+/', ' ', trim($data['name'])),
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'cover_letter' => $data['cover_letter'] ?? null,
            'cv_path' => $path,
            'cv_original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
            'cv_mime' => (string) $file->getMimeType(),
            'cv_size' => $file->getSize(),
            'consent_at' => now(),
            'ip' => $request->ip(),
        ]);

        Mail::to($router->hrAddress())->send(new ApplicationReceived($application));
        Mail::to($application->email)->send(new ApplicationReceived($application, forApplicant: true));

        return redirect()->route('careers.applied', $vacancy->slug)->with('status', 'Thank you. Your application has been received and we have emailed you a confirmation.');
    }

    /** Shown from the session-bearing route group so the flash message survives the redirect. */
    public function applied(string $slug): View
    {
        $vacancy = Vacancy::where('slug', $slug)->firstOrFail();

        return view('site.careers.applied', ['vacancy' => $vacancy]);
    }

    private function openVacancy(string $slug): Vacancy
    {
        return Vacancy::open()->where('slug', $slug)->firstOrFail();
    }
}
