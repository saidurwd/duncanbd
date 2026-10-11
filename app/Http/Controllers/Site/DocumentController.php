<?php

namespace App\Http\Controllers\Site;

use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Models\DocumentCategory;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(Request $request, ?string $category = null): View
    {
        $current = $category ? DocumentCategory::where('slug', $category)->firstOrFail() : null;
        $year = preg_match('/^\d{4}(-\d{4})?$/', (string) $request->query('year')) ? $request->query('year') : null;

        $documents = Document::live()->with(['category', 'currentVersion', 'cover.media'])
            ->when($current, fn ($q) => $q->where('category_id', $current->id))
            ->when($year, fn ($q) => $q->where('financial_year', $year))
            ->orderByDesc('document_date')->orderByDesc('id')
            ->paginate(20)->withQueryString();

        return view('site.documents.index', [
            'documents' => $documents,
            'category' => $current,
            'categories' => DocumentCategory::orderBy('sort_order')->get(),
            'years' => Document::live()->whereNotNull('financial_year')->distinct()->orderByDesc('financial_year')->pluck('financial_year'),
            'year' => $year,
        ]);
    }

    public function show(string $category, string $slug): View
    {
        $cat = DocumentCategory::where('slug', $category)->firstOrFail();
        $document = Document::live()->with(['category', 'currentVersion', 'cover.media'])
            ->where('category_id', $cat->id)->where('slug', $slug)->firstOrFail();

        return view('site.documents.show', ['document' => $document]);
    }

    /** Public files only; private documents are served by the authenticated admin route. */
    public function download(string $uuid): StreamedResponse
    {
        $document = Document::live()->with('currentVersion')->where('uuid', $uuid)->firstOrFail();
        $version = $document->currentVersion ?? abort(404);

        $document->increment('download_count');

        return Storage::disk('local')->download($version->path, $version->original_name, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }
}
