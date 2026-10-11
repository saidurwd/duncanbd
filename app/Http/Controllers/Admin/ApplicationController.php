<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Careers\Enums\ApplicationStatus;
use App\Domain\Careers\Models\Application;
use App\Http\Controllers\Controller;
use App\Support\Csv;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.applications.index', [
            'applications' => Application::with('vacancy')
                ->when($request->integer('vacancy'), fn ($q, $id) => $q->where('vacancy_id', $id))
                ->when(ApplicationStatus::tryFrom((string) $request->query('status')), fn ($q, $s) => $q->where('status', $s->value))
                ->latest('id')->paginate(30)->withQueryString(),
        ]);
    }

    public function show(Application $application): View
    {
        return view('admin.applications.show', ['application' => $application->load('vacancy')]);
    }

    public function update(Request $request, Application $application): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(ApplicationStatus::class)],
            'internal_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $application->update($data);

        return back()->with('status', 'Application updated.');
    }

    /** CVs live on the private disk and are only ever served here, behind the careers permission. */
    public function cv(Application $application): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($application->cv_path), 404);

        activity('careers')->causedBy(auth()->user())->performedOn($application)->log('cv downloaded');

        return Storage::disk('local')->download($application->cv_path, $application->cv_original_name, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function export(Request $request): StreamedResponse
    {
        $rows = Application::with('vacancy')
            ->when($request->integer('vacancy'), fn ($q, $id) => $q->where('vacancy_id', $id))
            ->orderBy('id')->lazy()
            ->map(fn ($a) => [$a->id, $a->vacancy->title, $a->name, $a->email, $a->phone, $a->status->value, $a->created_at]);

        return Csv::download('applications-'.now()->format('Y-m-d').'.csv', ['id', 'vacancy', 'name', 'email', 'phone', 'status', 'applied_at'], $rows);
    }
}
