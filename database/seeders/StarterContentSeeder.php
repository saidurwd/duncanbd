<?php

namespace Database\Seeders;

use App\Domain\Cms\Actions\SaveMenu;
use App\Domain\Cms\Actions\SavePage;
use App\Domain\Cms\Enums\MenuLocation;
use App\Domain\Cms\Enums\PageStatus;
use App\Domain\Cms\Models\Menu;
use App\Domain\Cms\Models\Page;
use App\Domain\Identity\Models\User;
use Illuminate\Database\Seeder;

/**
 * Optional starter structure following the sitemap in the architecture doc (§4): the home page and
 * the section pages, built from page-builder blocks. Pages are created as DRAFTS with placeholder
 * copy for the content owner to replace; nothing is published automatically.
 *
 *   php artisan db:seed --class=StarterContentSeeder
 */
class StarterContentSeeder extends Seeder
{
    public function run(SavePage $save): void
    {
        $user = User::role('super-admin')->first() ?? User::first();

        if (! $user) {
            $this->command->warn('Create an admin user first (db:seed with ADMIN_SEED_EMAIL set).');

            return;
        }

        $pages = [
            'home' => ['Home', null, [
                $this->block('hero', ['heading' => 'Rooted in Bangladesh since 1859', 'intro' => 'REPLACE: one-sentence introduction.', 'slides' => []]),
                $this->block('quick_links', ['items' => [
                    ['label' => 'What we do', 'url' => '/what-we-do'],
                    ['label' => 'Our people', 'url' => '/who-we-are/leadership'],
                    ['label' => 'Investors', 'url' => '/investors'],
                    ['label' => 'Governance', 'url' => '/who-we-are/governance'],
                    ['label' => 'Sustainability', 'url' => '/sustainability'],
                ]]),
                $this->block('document_list', ['heading' => 'Investor updates', 'category' => 'annual-reports', 'limit' => 3]),
                $this->block('stats', ['items' => [
                    ['value' => 'REPLACE', 'label' => 'tea factories (confirm with Finance)'],
                    ['value' => 'REPLACE', 'label' => 'rubber plants (confirm with Finance)'],
                    ['value' => 'REPLACE', 'label' => 'hectares under management (confirm with Finance)'],
                ]]),
                $this->block('rich_text', ['heading' => 'Our purpose', 'body' => '<p>REPLACE: purpose statement approved by Corporate Affairs.</p>']),
                $this->block('news_feed', ['heading' => 'News', 'limit' => 3]),
            ]],
            'who-we-are' => ['Who we are', null, [$this->block('rich_text', ['heading' => 'About Duncan Brothers', 'body' => '<p>REPLACE</p>'])]],
            'who-we-are/history' => ['Our history', 'who-we-are', [$this->block('timeline', ['items' => []])]],
            'who-we-are/leadership' => ['Leadership & Board', 'who-we-are', [
                $this->block('people_grid', ['heading' => 'Board', 'group' => 'board']),
                $this->block('people_grid', ['heading' => 'Leadership', 'group' => 'leadership']),
            ]],
            'who-we-are/governance' => ['Corporate governance', 'who-we-are', [
                $this->block('document_list', ['heading' => 'Policies', 'category' => 'policies', 'limit' => 20]),
            ]],
            'what-we-do' => ['What we do', null, [$this->block('company_accordion', ['items' => []])]],
            'sustainability' => ['Sustainability', null, [
                $this->block('rich_text', ['heading' => 'Sustainability', 'body' => '<p>REPLACE</p>']),
                $this->block('document_list', ['heading' => 'Sustainability reports', 'category' => 'sustainability-reports', 'limit' => 10]),
            ]],
            'investors' => ['Investors & shareholders', null, [
                $this->block('document_list', ['heading' => 'Annual reports', 'category' => 'annual-reports', 'limit' => 10]),
                $this->block('document_list', ['heading' => 'AGM & EGM notices', 'category' => 'agm-egm-notices', 'limit' => 10]),
                $this->block('document_list', ['heading' => 'Statutory disclosures', 'category' => 'statutory-disclosures', 'limit' => 10]),
            ]],
        ];

        foreach ($pages as $path => [$title, $parentPath, $blocks]) {
            if (Page::where('path', $path)->exists()) {
                continue;
            }

            $parent = $parentPath ? Page::where('path', $parentPath)->first() : null;
            $page = $save(null, [
                'title' => $title,
                'slug' => basename($path),
                'parent_id' => $parent?->id,
                'blocks' => $blocks,
            ], $user, 'Starter content');

            // Only drafts: a human reviews and publishes.
            $page->update(['status' => PageStatus::Draft]);
        }

        $this->seedMenu($pages);
    }

    /** @param array<string, mixed> $pages */
    private function seedMenu(array $pages): void
    {
        $items = [];
        foreach (['who-we-are', 'what-we-do', 'sustainability', 'investors'] as $path) {
            $page = Page::where('path', $path)->first();
            if ($page) {
                $items[] = ['label' => $page->title, 'page_id' => $page->id, 'is_active' => true];
            }
        }
        $items[] = ['label' => 'News', 'url' => '/news', 'is_active' => true];

        if (! Menu::where('location', MenuLocation::Explore->value)->exists()) {
            app(SaveMenu::class)(MenuLocation::Explore, $items);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{type: string, data: array<string, mixed>}
     */
    private function block(string $type, array $data): array
    {
        return ['type' => $type, 'data' => $data];
    }
}
