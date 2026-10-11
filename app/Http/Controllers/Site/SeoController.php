<?php

namespace App\Http\Controllers\Site;

use App\Domain\Cms\Models\Page;
use App\Domain\Cms\RedirectManager;
use App\Domain\Settings\SettingsRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $xml = Cache::remember('sitemap.xml', 3600, function () {
            $sitemap = Sitemap::create();

            foreach (Page::live()->orderBy('path')->get(['path', 'updated_at']) as $page) {
                $sitemap->add(Url::create(url(RedirectManager::pageUrl($page)))->setLastModificationDate($page->updated_at));
            }

            return $sitemap->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function robots(SettingsRepository $settings): Response
    {
        $custom = $settings->get('seo', 'robots_txt');

        $body = filled($custom) ? $custom : implode("\n", [
            'User-agent: *',
            'Disallow: /'.config('admin.path').'/',
            'Disallow: /preview/',
            '',
        ]);

        return response(rtrim($body)."\n\nSitemap: ".url('/sitemap.xml')."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
