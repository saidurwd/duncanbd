<?php

namespace App\Listeners;

use App\Domain\Cms\MenuRepository;
use Illuminate\Support\Facades\Cache;
use Spatie\ResponseCache\Facades\ResponseCache;

/** Anything that changes public output clears the guest page cache and derived artefacts. */
class InvalidatePublicCaches
{
    public function __construct(private MenuRepository $menus) {}

    public function handle(object $event): void
    {
        ResponseCache::clear();
        Cache::forget('sitemap.xml');
        $this->menus->forget();
    }
}
