<?php

namespace App\Http\Controllers\Site;

use App\Domain\Business\Models\Business;
use App\Domain\Careers\Models\Vacancy;
use App\Domain\Cms\Models\Page;
use App\Domain\Cms\RedirectManager;
use App\Domain\Documents\Models\Document;
use App\Domain\News\Models\Post;
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
                $isHome = $page->path === 'home';
                $sitemap->add(Url::create(url(RedirectManager::pageUrl($page)))->setLastModificationDate($page->updated_at)
                    ->setPriority($isHome ? 1.0 : 0.7)->setChangeFrequency($isHome ? Url::CHANGE_FREQUENCY_WEEKLY : Url::CHANGE_FREQUENCY_MONTHLY));
            }

            // Listing pages
            foreach ([route('news.index', [], false), route('documents.index', [], false), route('careers.index', [], false), route('contact', [], false)] as $path) {
                $sitemap->add(Url::create(url($path))->setPriority(0.6)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY));
            }

            foreach (Post::live()->latest('published_at')->get(['slug', 'published_at', 'updated_at']) as $post) {
                $sitemap->add(Url::create(url($post->url()))->setLastModificationDate($post->updated_at)->setPriority(0.6)->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY));
            }

            foreach (Document::live()->with('category')->get() as $document) {
                $sitemap->add(Url::create(route('documents.show', [$document->category->slug, $document->slug]))->setLastModificationDate($document->updated_at)->setPriority(0.6)->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY));
            }

            foreach (Vacancy::open()->get(['slug', 'updated_at']) as $vacancy) {
                $sitemap->add(Url::create(route('careers.show', $vacancy->slug))->setLastModificationDate($vacancy->updated_at)->setPriority(0.5)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY));
            }

            foreach (Business::with(['estates' => fn ($q) => $q->where('is_active', true)])->where('is_active', true)->get() as $business) {
                // A business URL is also an editor-built page when one exists at the same path; that page is already listed.
                if (! Page::live()->where('path', 'what-we-do/'.$business->slug)->exists()) {
                    $sitemap->add(Url::create(route('business.show', $business->slug))->setLastModificationDate($business->updated_at)->setPriority(0.5));
                }
                foreach ($business->estates as $estate) {
                    $sitemap->add(Url::create(route('business.estate', [$business->slug, $estate->slug]))->setLastModificationDate($estate->updated_at)->setPriority(0.4));
                }
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
