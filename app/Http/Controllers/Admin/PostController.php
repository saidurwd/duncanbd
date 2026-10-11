<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Documents\Enums\DocumentStatus;
use App\Domain\News\Actions\SavePost;
use App\Domain\News\Models\NewsCategory;
use App\Domain\News\Models\Post;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SavePostRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        return view('admin.posts.index', [
            'posts' => Post::with(['category', 'author'])
                ->when($request->query('type'), fn ($q, $t) => $q->where('type', $t))
                ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
                ->when($search !== '', fn ($q) => $q->where('title', 'like', '%'.addcslashes($search, '%_\\').'%'))
                ->latest('id')->paginate(25)->withQueryString(),
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return $this->form(new Post(['type' => 'news', 'status' => DocumentStatus::Draft]));
    }

    public function store(SavePostRequest $request, SavePost $save): RedirectResponse
    {
        $post = $save(null, $request->validated(), $request->user());

        return redirect()->route('admin.posts.edit', $post)->with('status', 'Saved as draft.');
    }

    public function edit(Post $post): View
    {
        abort_unless(auth()->user()->can('news.edit'), 403);

        return $this->form($post);
    }

    public function update(SavePostRequest $request, Post $post, SavePost $save): RedirectResponse
    {
        $save($post, $request->validated(), $request->user());

        return back()->with('status', 'Saved.');
    }

    public function publish(Request $request, Post $post): RedirectResponse
    {
        abort_unless($request->user()->can('pages.publish'), 403);

        $data = $request->validate(['published_at' => ['nullable', 'date']]);
        $when = isset($data['published_at']) ? now()->parse($data['published_at'], 'Asia/Dhaka')->utc() : ($post->published_at ?? now());

        $post->update(['status' => DocumentStatus::Published, 'published_at' => $when]);

        return back()->with('status', $when->isFuture() ? 'Scheduled for '.$when->timezone('Asia/Dhaka')->format('d M Y H:i').'.' : 'Published.');
    }

    public function unpublish(Request $request, Post $post): RedirectResponse
    {
        abort_unless($request->user()->can('pages.publish'), 403);

        $post->update(['status' => DocumentStatus::Archived]);

        return back()->with('status', 'Archived.');
    }

    public function destroy(Request $request, Post $post): RedirectResponse
    {
        abort_unless($request->user()->can('pages.publish'), 403);

        $post->delete();

        return redirect()->route('admin.posts.index')->with('status', 'Moved to trash.');
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('news.edit'), 403);

        $data = $request->validate(['name' => ['required', 'string', 'max:120']]);
        $base = Str::slug($data['name']);
        $slug = $base;
        for ($n = 2; NewsCategory::where('slug', $slug)->exists(); $n++) {
            $slug = "$base-$n";
        }
        NewsCategory::create(['name' => $data['name'], 'slug' => $slug]);

        return back()->with('status', 'Category added.');
    }

    private function form(Post $post): View
    {
        return view('admin.posts.form', ['post' => $post, 'categories' => NewsCategory::orderBy('name')->get()]);
    }
}
