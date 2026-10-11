<?php

namespace App\Http\Controllers\Site;

use App\Domain\Cms\Blocks\BlockMedia;
use App\Domain\Cms\Models\Page;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

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

    private function render(Page $page, bool $preview = false): View
    {
        $blocks = $page->blocks ?? [];

        return view('site.page', [
            'page' => $page,
            'blocks' => $blocks,
            'media' => BlockMedia::forBlocks($blocks),
            'preview' => $preview,
        ]);
    }
}
