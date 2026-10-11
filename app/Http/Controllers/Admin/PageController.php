<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Cms\Actions\SavePage;
use App\Domain\Cms\Actions\TransitionPage;
use App\Domain\Cms\Blocks\BlockType;
use App\Domain\Cms\Enums\PageStatus;
use App\Domain\Cms\Models\Page;
use App\Domain\Cms\Models\Revision;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SavePageRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class PageController extends Controller
{
    public function index(Request $request): View
    {
        $status = PageStatus::tryFrom((string) $request->query('status'));
        $search = trim((string) $request->query('q'));

        return view('admin.pages.index', [
            'pages' => Page::query()->with('author')
                ->when($status, fn ($q) => $q->where('status', $status->value))
                ->when($search !== '', fn ($q) => $q->where('title', 'like', '%'.addcslashes($search, '%_\\').'%'))
                ->orderBy('path')
                ->paginate(30)
                ->withQueryString(),
            'status' => $status,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return $this->form(new Page(['status' => PageStatus::Draft, 'template' => 'default', 'blocks' => []]));
    }

    public function store(SavePageRequest $request, SavePage $save): RedirectResponse
    {
        $page = $save(null, $request->validated(), $request->user(), $request->input('note'));

        return redirect()->route('admin.pages.edit', $page)->with('status', 'Page created as draft.');
    }

    public function edit(Page $page): View
    {
        abort_unless(auth()->user()->can('pages.edit'), 403);

        return $this->form($page);
    }

    public function update(SavePageRequest $request, Page $page, SavePage $save): RedirectResponse
    {
        $save($page, $request->validated(), $request->user(), $request->input('note'));

        return back()->with('status', 'Page saved.');
    }

    public function transition(Request $request, Page $page, TransitionPage $transition): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', array_column(PageStatus::cases(), 'value'))],
            'published_at' => ['nullable', 'date'],
            'unpublish_at' => ['nullable', 'date', 'after:published_at'],
        ]);

        $transition(
            $page,
            PageStatus::from($data['status']),
            $request->user(),
            isset($data['published_at']) ? now()->parse($data['published_at'], 'Asia/Dhaka')->utc() : null,
            isset($data['unpublish_at']) ? now()->parse($data['unpublish_at'], 'Asia/Dhaka')->utc() : null,
        );

        return back()->with('status', 'Page is now '.$page->fresh()->status->label().'.');
    }

    public function revisions(Page $page): View
    {
        abort_unless(auth()->user()->can('pages.edit'), 403);

        return view('admin.pages.revisions', ['page' => $page, 'revisions' => $page->revisions()->with('user')->paginate(30)]);
    }

    public function restore(Request $request, Page $page, Revision $revision, SavePage $save): RedirectResponse
    {
        abort_unless($request->user()->can('pages.edit') && $revision->revisionable_id === $page->id && $revision->revisionable_type === $page->getMorphClass(), 403);

        $payload = $revision->payload;
        $save($page, $payload, $request->user(), "Restored revision #{$revision->id}");

        return redirect()->route('admin.pages.edit', $page)->with('status', 'Revision restored as the current draft.');
    }

    public function preview(Page $page): RedirectResponse
    {
        abort_unless(auth()->user()->can('pages.edit'), 403);

        return redirect(URL::temporarySignedRoute('preview.page', now()->addHour(), ['page' => $page->id]));
    }

    public function destroy(Page $page): RedirectResponse
    {
        abort_unless(auth()->user()->can('pages.publish'), 403);
        abort_if($page->children()->exists(), 422, 'Move or delete the child pages first.');

        $page->delete();

        return redirect()->route('admin.pages.index')->with('status', 'Page moved to trash.');
    }

    private function form(Page $page): View
    {
        return view('admin.pages.form', [
            'page' => $page,
            'parents' => Page::where('id', '!=', $page->id ?? 0)->orderBy('path')->get(['id', 'title', 'path']),
            'blockSchema' => collect(BlockType::cases())->mapWithKeys(fn (BlockType $t) => [$t->value => ['label' => $t->label(), 'fields' => $t->fields()]]),
            'initialBlocks' => old('blocks_json') ? json_decode(old('blocks_json'), true) : ($page->blocks ?? []),
            'nextStatuses' => collect($page->exists ? $page->status->transitions() : [])
                ->filter(fn ($perm) => auth()->user()->can($perm))
                ->keys()
                ->map(fn ($s) => PageStatus::from($s)),
        ]);
    }
}
