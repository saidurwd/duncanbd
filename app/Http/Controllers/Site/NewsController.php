<?php

namespace App\Http\Controllers\Site;

use App\Domain\News\Models\NewsCategory;
use App\Domain\News\Models\Post;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index(Request $request): View
    {
        $type = in_array($request->query('type'), ['news', 'press'], true) ? $request->query('type') : null;
        $category = $request->query('category') ? NewsCategory::where('slug', $request->query('category'))->first() : null;

        return view('site.news.index', [
            'posts' => Post::live()->with(['category', 'featured.media'])
                ->when($type, fn ($q) => $q->where('type', $type))
                ->when($category, fn ($q) => $q->where('category_id', $category->id))
                ->orderByDesc('published_at')->paginate(12)->withQueryString(),
            'categories' => NewsCategory::orderBy('name')->get(),
            'type' => $type,
            'category' => $category,
        ]);
    }

    public function show(int $year, string $slug): View
    {
        $post = Post::live()->with(['category', 'featured.media'])->where('slug', $slug)->firstOrFail();

        // Canonical URL carries the publication year.
        abort_unless($post->published_at->year === $year, 404);

        return view('site.news.show', ['post' => $post]);
    }
}
