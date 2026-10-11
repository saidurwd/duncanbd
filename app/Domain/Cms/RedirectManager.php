<?php

namespace App\Domain\Cms;

use App\Domain\Cms\Models\NotFoundEntry;
use App\Domain\Cms\Models\Page;
use App\Domain\Cms\Models\Redirect;
use Illuminate\Support\Str;

/** Looks up, records and maintains redirects and the 404 log. */
class RedirectManager
{
    /** "/About-Us/?utm=x" -> "/about-us"; "/" stays "/". */
    public static function normalize(string $path): string
    {
        $path = '/'.ltrim(Str::before($path, '?'), '/');

        return Str::lower($path === '/' ? '/' : rtrim($path, '/'));
    }

    public static function pageUrl(Page $page): string
    {
        return $page->path === 'home' ? '/' : '/'.$page->path;
    }

    public function find(string $path): ?Redirect
    {
        return Redirect::where('from_path', self::normalize($path))->first();
    }

    public function recordHit(Redirect $redirect): void
    {
        $redirect->newQuery()->whereKey($redirect->getKey())->update([
            'hits' => $redirect->hits + 1,
            'last_hit_at' => now(),
        ]);
    }

    public function recordNotFound(string $path, ?string $referrer): void
    {
        $path = self::normalize($path);

        if (strlen($path) > 500) {
            return;
        }

        $entry = NotFoundEntry::firstOrCreate(
            ['path' => $path],
            ['referrer' => $referrer ? Str::limit($referrer, 500, '') : null, 'hits' => 0, 'last_seen_at' => now()],
        );
        $entry->update(['hits' => $entry->hits + 1, 'last_seen_at' => now()]);
    }

    /** Page moved: 301 old -> new, repoint older redirects at the new URL and drop any that would loop. */
    public function pageMoved(Page $page, string $oldPath): void
    {
        $from = self::normalize($oldPath === 'home' ? '/' : '/'.$oldPath);
        $to = self::pageUrl($page);

        if ($from === $to) {
            return;
        }

        Redirect::where('from_path', $to)->delete();
        Redirect::where('to_url', $from)->update(['to_url' => $to]);

        Redirect::updateOrCreate(
            ['from_path' => $from],
            ['to_url' => $to, 'status_code' => 301, 'is_automatic' => true],
        );
    }
}
