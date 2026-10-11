<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Careers\Actions\SaveVacancy;
use App\Domain\Careers\Enums\EmploymentType;
use App\Domain\Careers\Models\Vacancy;
use App\Domain\Documents\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VacancyController extends Controller
{
    public function index(): View
    {
        return view('admin.vacancies.index', ['vacancies' => Vacancy::withCount('applications')->latest('id')->paginate(30)]);
    }

    public function create(): View
    {
        return view('admin.vacancies.form', ['vacancy' => new Vacancy(['employment_type' => EmploymentType::FullTime, 'status' => DocumentStatus::Draft])]);
    }

    public function store(Request $request, SaveVacancy $save): RedirectResponse
    {
        $vacancy = $save(null, $this->validated($request));

        return redirect()->route('admin.vacancies.edit', $vacancy)->with('status', 'Vacancy saved as draft.');
    }

    public function edit(Vacancy $vacancy): View
    {
        return view('admin.vacancies.form', ['vacancy' => $vacancy]);
    }

    public function update(Request $request, Vacancy $vacancy, SaveVacancy $save): RedirectResponse
    {
        $save($vacancy, $this->validated($request));

        return back()->with('status', 'Vacancy updated.');
    }

    public function publish(Vacancy $vacancy): RedirectResponse
    {
        $vacancy->update(['status' => DocumentStatus::Published, 'published_at' => $vacancy->published_at ?? now(), 'closed_at' => null]);

        return back()->with('status', 'Vacancy published.');
    }

    public function close(Vacancy $vacancy): RedirectResponse
    {
        $vacancy->update(['status' => DocumentStatus::Archived, 'closed_at' => now()]);

        return back()->with('status', 'Vacancy closed.');
    }

    public function destroy(Vacancy $vacancy): RedirectResponse
    {
        $vacancy->delete();

        return redirect()->route('admin.vacancies.index')->with('status', 'Vacancy moved to trash.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:160', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'department' => ['nullable', 'string', 'max:120'],
            'location' => ['nullable', 'string', 'max:120'],
            'employment_type' => ['required', Rule::enum(EmploymentType::class)],
            'description' => ['nullable', 'string', 'max:50000'],
            'requirements' => ['nullable', 'string', 'max:50000'],
            'closing_date' => ['nullable', 'date'],
        ]);
    }
}
