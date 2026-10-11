<?php

namespace App\Http\Controllers\Site;

use App\Domain\Cms\Blocks\BlockMedia;
use App\Domain\Cms\Blocks\BlockType;
use App\Domain\Cms\Models\Page;
use App\Domain\Media\Models\MediaItem;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

class PageController extends Controller
{
    public function home(): View
    {
        $page = Page::live()->where('path', 'home')->first();

        return $page ? $this->render($page) : view('site.coming-soon');
    }

    public function show(string $path): View
    {
        // "home" is served at "/" only.
        abort_if($path === 'home', 404);

        return $this->render(Page::live()->where('path', trim($path, '/'))->firstOrFail());
    }

    /** Signed, time-limited draft preview for reviewers. */
    public function preview(Page $page): View
    {
        return $this->render($page, preview: true);
    }

    public function renderPage(Page $page): View
    {
        return $this->render($page);
    }

    private function render(Page $page, bool $preview = false): View
    {
        $blocks = $this->withDefaults($page->blocks ?? []);

        $media = BlockMedia::forBlocks($blocks);
        $ogId = $page->seo['og_media_id'] ?? null;
        $og = $ogId ? $media->get($ogId) ?? MediaItem::with('media')->find($ogId) : null;

        return view('site.page', [
            'page' => $page,
            'blocks' => $blocks,
            'media' => $media,
            'preview' => $preview,
            'autoDescription' => $this->excerpt($blocks),
            'ogImage' => $og?->getFirstMediaUrl('file', 'large') ?: null,
            'breadcrumbs' => $page->path === 'home' ? [] : $this->breadcrumbs($page),
        ]);
    }

    /**
     * First readable text on the page, used as the meta description when the editor has not written one.
     *
     * @param  list<array<string, mixed>>  $blocks
     */
    private function excerpt(array $blocks): ?string
    {
        foreach ($blocks as $block) {
            $text = match ($block['type']) {
                'hero' => $block['data']['intro'] ?? null,
                'rich_text' => $block['data']['body'] ?? null,
                'cta_band' => $block['data']['text'] ?? null,
                default => null,
            };
            $text = trim((string) preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string) $text))));

            if ($text !== '') {
                return Str::limit($text, 160);
            }
        }

        return null;
    }

    /**
     * Blocks saved before a schema gained a field lack that key; give every block every field.
     *
     * @param  array<int, array<string, mixed>>  $blocks
     * @return list<array<string, mixed>>
     */
    private function withDefaults(array $blocks): array
    {
        $out = [];

        foreach ($blocks as $block) {
            $type = BlockType::tryFrom((string) ($block['type'] ?? ''));

            if ($type) {
                $block['data'] = array_merge(array_fill_keys(array_keys($type->fields()), null), (array) ($block['data'] ?? []));
                $out[] = $block;
            }
        }

        return $out;
    }

    /** @return list<array{label: string, url: string}> Home plus each live ancestor that exists as a page. */
    private function breadcrumbs(Page $page): array
    {
        $segments = explode('/', $page->path);
        $paths = [];
        foreach ($segments as $i => $_) {
            $paths[] = implode('/', array_slice($segments, 0, $i + 1));
        }

        $trail = Page::live()->whereIn('path', $paths)->get(['title', 'path'])->keyBy('path');
        $crumbs = [['label' => 'Home', 'url' => '/']];

        foreach ($paths as $path) {
            if ($path === $page->path) {
                $crumbs[] = ['label' => $page->title, 'url' => '/'.$path];
            } elseif ($ancestor = $trail->get($path)) {
                $crumbs[] = ['label' => $ancestor->title, 'url' => '/'.$path];
            }
        }

        return $crumbs;
    }
}
