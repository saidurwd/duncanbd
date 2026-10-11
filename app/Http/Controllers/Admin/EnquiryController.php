<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Engagement\Enums\EnquiryCategory;
use App\Domain\Engagement\Enums\EnquiryStatus;
use App\Domain\Engagement\Models\Enquiry;
use App\Domain\Identity\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EnquiryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        return view('admin.enquiries.index', [
            'enquiries' => Enquiry::with('assignee')
                ->when(EnquiryCategory::tryFrom((string) $request->query('category')), fn ($q, $c) => $q->where('category', $c->value))
                ->when(EnquiryStatus::tryFrom((string) $request->query('status')), fn ($q, $s) => $q->where('status', $s->value))
                ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', '%'.addcslashes($search, '%_\\').'%')->orWhere('email', 'like', '%'.addcslashes($search, '%_\\').'%')))
                ->latest('id')->paginate(30)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function show(Enquiry $enquiry): View
    {
        return view('admin.enquiries.show', [
            'enquiry' => $enquiry->load('assignee'),
            'staff' => User::where('is_active', true)->permission('enquiries.manage')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Enquiry $enquiry): RedirectResponse
    {
        abort_unless($request->user()->can('enquiries.manage'), 403);

        $data = $request->validate([
            'status' => ['required', Rule::enum(EnquiryStatus::class)],
            'assigned_to' => ['nullable', Rule::exists('users', 'id')],
            'internal_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $enquiry->update($data);

        return back()->with('status', 'Enquiry updated.');
    }

    public function destroy(Request $request, Enquiry $enquiry): RedirectResponse
    {
        abort_unless($request->user()->can('enquiries.manage'), 403);

        $enquiry->delete();

        return redirect()->route('admin.enquiries.index')->with('status', 'Enquiry deleted.');
    }
}
