<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Documents\Actions\SaveDocument;
use App\Domain\Documents\Enums\DocumentStatus;
use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Models\DocumentCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveDocumentRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Document::class);

        $user = $request->user();
        $search = trim((string) $request->query('q'));

        // Hide investor documents from users who cannot manage them.
        $visibleCategories = DocumentCategory::orderBy('sort_order')->get()
            ->filter(fn ($c) => $user->can('manageCategory', [Document::class, $c]));

        return view('admin.documents.index', [
            'documents' => Document::query()->with(['category', 'currentVersion'])
                ->whereIn('category_id', $visibleCategories->pluck('id'))
                ->when($request->integer('category'), fn ($q, $id) => $q->where('category_id', $id))
                ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
                ->when($request->query('year'), fn ($q, $y) => $q->where('financial_year', $y))
                ->when($search !== '', fn ($q) => $q->where('title', 'like', '%'.addcslashes($search, '%_\\').'%'))
                ->latest('id')->paginate(25)->withQueryString(),
            'categories' => $visibleCategories,
            'search' => $search,
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('viewAny', Document::class);

        return $this->form(new Document(['visibility' => 'public', 'status' => DocumentStatus::Draft]), $request);
    }

    public function store(SaveDocumentRequest $request, SaveDocument $save): RedirectResponse
    {
        $document = $save(null, $request->validated(), $request->file('file'), $request->user());

        return redirect()->route('admin.documents.edit', $document)->with('status', 'Document saved as draft.');
    }

    public function edit(Document $document, Request $request): View
    {
        Gate::authorize('update', $document);

        return $this->form($document->load(['versions.document', 'category']), $request);
    }

    public function update(SaveDocumentRequest $request, Document $document, SaveDocument $save): RedirectResponse
    {
        $save($document, $request->validated(), $request->file('file'), $request->user());

        return back()->with('status', 'Document updated.');
    }

    public function publish(Request $request, Document $document): RedirectResponse
    {
        Gate::authorize('update', $document);
        abort_unless($document->currentVersion()->exists(), 422, 'Upload a file before publishing.');

        $document->update([
            'status' => DocumentStatus::Published,
            'published_at' => $document->published_at ?? now(),
        ]);

        return back()->with('status', 'Document published.');
    }

    public function unpublish(Document $document): RedirectResponse
    {
        Gate::authorize('update', $document);

        $document->update(['status' => DocumentStatus::Archived]);

        return back()->with('status', 'Document archived.');
    }

    public function destroy(Document $document): RedirectResponse
    {
        Gate::authorize('delete', $document);

        $document->delete();

        return redirect()->route('admin.documents.index')->with('status', 'Document moved to trash.');
    }

    /** Staff download (any status/visibility); every file is served through the policy check. */
    public function download(Document $document, int $version): StreamedResponse
    {
        Gate::authorize('update', $document);

        $file = $document->versions()->where('version', $version)->firstOrFail();
        abort_unless(Storage::disk('local')->exists($file->path), 404);

        return Storage::disk('local')->download($file->path, $file->original_name, [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function form(Document $document, Request $request): View
    {
        $user = $request->user();

        return view('admin.documents.form', [
            'document' => $document,
            'categories' => DocumentCategory::orderBy('sort_order')->get()
                ->filter(fn ($c) => $user->can('manageCategory', [Document::class, $c])),
        ]);
    }
}
