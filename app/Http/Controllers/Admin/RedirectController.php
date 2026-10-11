<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Cms\Models\NotFoundEntry;
use App\Domain\Cms\Models\Redirect;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRedirectRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\ResponseCache\Facades\ResponseCache;

class RedirectController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        return view('admin.redirects.index', [
            'redirects' => Redirect::query()
                ->when($search !== '', fn ($q) => $q->where('from_path', 'like', '%'.addcslashes($search, '%_\\').'%'))
                ->latest('id')->paginate(30)->withQueryString(),
            'notFound' => NotFoundEntry::orderByDesc('hits')->limit(25)->get(),
            'search' => $search,
            'prefill' => $request->query('from'),
        ]);
    }

    public function store(StoreRedirectRequest $request): RedirectResponse
    {
        Redirect::create($request->validated());
        NotFoundEntry::where('path', $request->validated('from_path'))->delete();
        ResponseCache::clear();

        return redirect()->route('admin.redirects.index')->with('status', 'Redirect created.');
    }

    public function destroy(Request $request, Redirect $redirect): RedirectResponse
    {
        abort_unless($request->user()->can('seo.manage'), 403);

        $redirect->delete();
        ResponseCache::clear();

        return back()->with('status', 'Redirect removed.');
    }

    public function clearNotFound(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('seo.manage'), 403);

        NotFoundEntry::query()->delete();

        return back()->with('status', '404 log cleared.');
    }
}
