<?php

namespace App\Http\Controllers\Site;

use App\Domain\Cms\Models\Page;
use App\Domain\Cms\SiteSearch;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request, SiteSearch $search): View
    {
        $query = trim((string) $request->query('q', ''));

        return view('site.search', ['query' => $query, 'results' => $search->search($query)]);
    }

    public function sitemap(): View
    {
        return view('site.sitemap', ['pages' => Page::live()->where('path', '!=', 'home')->orderBy('path')->get(['title', 'path'])]);
    }
}
