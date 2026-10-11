<?php

namespace App\Domain\Cms\Actions;

use App\Domain\Cms\Blocks\BlockSanitizer;
use App\Domain\Cms\Enums\PageStatus;
use App\Domain\Cms\Events\PageContentChanged;
use App\Domain\Cms\Events\PagePathChanged;
use App\Domain\Cms\Models\Page;
use App\Domain\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SavePage
{
    public function __construct(private BlockSanitizer $sanitizer) {}

    /**
     * @param  array{title: string, slug?: ?string, parent_id?: ?int, template?: ?string, blocks?: list<array<string, mixed>>, seo?: ?array<string, mixed>}  $data
     */
    public function __invoke(?Page $page, array $data, User $user, ?string $note = null): Page
    {
        return DB::transaction(function () use ($page, $data, $user, $note) {
            $page ??= new Page(['status' => PageStatus::Draft, 'author_id' => $user->id]);
            $oldPath = $page->exists ? $page->path : null;

            $slug = Str::slug($data['slug'] ?? '') ?: Str::slug($data['title']);
            $parent = ! empty($data['parent_id']) ? Page::findOrFail($data['parent_id']) : null;

            // A page can't become its own ancestor.
            if ($parent && $page->exists && ($parent->id === $page->id || str_starts_with($parent->path.'/', $page->path.'/'))) {
                abort(422, 'A page cannot be moved under itself.');
            }

            $page->fill([
                'title' => $data['title'],
                'slug' => $slug,
                'parent_id' => $parent?->id,
                'path' => ltrim(($parent ? $parent->path.'/' : '').$slug, '/'),
                'template' => $data['template'] ?? 'default',
                'blocks' => $this->sanitizer->sanitize($data['blocks'] ?? []),
                'seo' => $data['seo'] ?? null,
            ])->save();

            if ($oldPath !== null && $oldPath !== $page->path) {
                $this->cascadePaths($page, $oldPath);
                PagePathChanged::dispatch($page, $oldPath);
            }

            $page->revisions()->create([
                'payload' => $page->only(['title', 'slug', 'parent_id', 'template', 'blocks', 'seo']),
                'user_id' => $user->id,
                'note' => $note,
            ]);

            PageContentChanged::dispatch($page);

            return $page;
        });
    }

    /** Re-base descendant paths after a slug/parent change, announcing each so redirects can follow. */
    private function cascadePaths(Page $page, string $oldPath): void
    {
        foreach ($page->children()->get() as $child) {
            $childOld = $child->path;
            $child->update(['path' => $page->path.'/'.$child->slug]);
            $this->cascadePaths($child, $childOld);
            PagePathChanged::dispatch($child, $childOld);
        }
    }
}
