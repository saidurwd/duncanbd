<?php

namespace Database\Seeders;

use App\Domain\Business\Models\Business;
use App\Domain\Business\Models\Estate;
use App\Domain\Cms\Actions\SaveMenu;
use App\Domain\Cms\Actions\SavePage;
use App\Domain\Cms\Enums\MenuLocation;
use App\Domain\Cms\Legacy\LegacyHtml;
use App\Domain\Cms\Legacy\LegacyImporter;
use App\Domain\Cms\Models\Page;
use App\Domain\Documents\Models\Document;
use App\Domain\Identity\Models\User;
use App\Domain\News\Models\NewsCategory;
use App\Domain\News\Models\Post;
use App\Domain\People\Models\TimelineEvent;
use App\Domain\Settings\SettingsRepository;
use Database\Seeders\Concerns\WipesContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Spatie\ResponseCache\Facades\ResponseCache;

/**
 * Loads the REAL content of the old duncanbd.com as the new site, ready to publish:
 * every page (history, products, tea and rubber, community programmes, associate companies), the 16 estates,
 * a history timeline, the Annual Report 2025, the photo gallery, contact details and the menus.
 *
 * Source: the crawl in storage/app/legacy (python3 tools/legacy/crawl.py). Nothing is invented: where the old site
 * has no information (people, vacancies, e-mail addresses, policies) the seeder leaves it out; the four legal pages are
 * created as drafts for the Company Secretary to write.
 *
 *   php artisan db:seed --class=LegacyContentSeeder
 *
 * It REPLACES all content. On production it refuses to run over existing content unless LEGACY_RESET=1.
 */
class LegacyContentSeeder extends Seeder
{
    use WipesContent;

    /** Photographs per old page: [hero slides..., gallery...]. Keys are the legacy page URLs. */
    private const PHOTOS = [
        '/content/2.html' => ['hero' => ['1486449130_tea_garden.jpg']],
        '/content/6.html' => ['hero' => ['1486449130_tea_garden.jpg', '1486437415_rubber_garden.jpg', '1486390358_duncans_water.jpg']],
        '/content/7.html' => ['hero' => ['1486449130_tea_garden.jpg', '1486445013_plucking_2.jpg', '1486444602_section_with_new_shade_sets.jpg'], 'gallery' => ['1486439110_nursery_1.jpg', '1486442537_nursery_2.jpg', '1486444944_plucking_1.jpg', '1486445125_plucking_3.jpg', '1486449199_factory.jpg', '1486449232_karimpore_factory.jpg']],
        '/content/8.html' => ['hero' => ['1486449199_factory.jpg', '1486444944_plucking_1.jpg'], 'gallery' => ['1486439110_nursery_1.jpg', '1486444602_section_with_new_shade_sets.jpg', '1486445125_plucking_3.jpg', '1486449232_karimpore_factory.jpg']],
        '/content/9.html' => ['hero' => ['1486449130_tea_garden.jpg']],
        '/content/10.html' => ['hero' => ['1486437415_rubber_garden.jpg', '1486437447_tapping_(female_worker).jpg'], 'gallery' => ['1486437496_tapping_(male_worker).jpg', '1486437525_rubber_collection.jpg', '1486438478_raw_materials.jpg']],
        '/content/11.html' => ['hero' => ['1486437574_milling_machine.jpg', '1486438781_rss_sheet_ready_to_be_smoked.jpg'], 'gallery' => ['1486437614_rss_sheet_rolled_in_milling_machine.jpg', '1486437638_rss_sheet_after_cogulation.jpg', '1486437967_rss.jpg']],
        '/content/12.html' => ['hero' => ['1486437415_rubber_garden.jpg']],
        '/content/13.html' => ['hero' => ['1486390358_duncans_water.jpg', '1486390267_dpl_factory.jpg'], 'gallery' => ['1486390292_dpl_labeling.jpg', '1486390316_dpl_quality_test.jpg']],
        '/content/14.html' => ['hero' => ['1486449381_cdfs_students.jpg', '1486449433_cdf_hospital.jpg']],
        '/content/15.html' => ['hero' => ['1486449615_clinic.jpg'], 'gallery' => ['1486449570_cdf_hospital_ward.jpg', '1486449595_cdfh_ward.jpg']],
        '/content/16.html' => ['hero' => ['1486449433_cdf_hospital.jpg'], 'gallery' => ['1486449469_diagnostics.jpg', '1486449503_cdfh_ot.jpg', '1486449570_cdf_hospital_ward.jpg', '1486449595_cdfh_ward.jpg']],
        '/content/17.html' => ['hero' => ['1486449341_cdf_school.jpg'], 'gallery' => ['1486449381_cdfs_students.jpg', '1486449410_cdfs_playground.jpg']],
        '/content/18.html' => ['hero' => ['1486391126_orchid_garden.jpg'], 'gallery' => ['1486390484_orchid_(pink).jpg', '1486390594_orchid_(purple).jpg', '1486390804_orchid_(yellow).jpg']],
        '/content/20.html' => ['hero' => ['1486444602_section_with_new_shade_sets.jpg']],
        '/content/23.html' => ['hero' => ['1486390267_dpl_factory.jpg'], 'gallery' => ['1486390292_dpl_labeling.jpg', '1486390316_dpl_quality_test.jpg', '1486390358_duncans_water.jpg']],
        '/content/24.html' => ['gallery' => ['cwl.jpg']],
    ];

    /** Old pages whose nav title is generic ("Production") get a clear title and a fixed slug. */
    private const TITLES = [
        '/content/8.html' => ['Tea production', 'production'], '/content/9.html' => ['Tea gardens', 'gardens'],
        '/content/11.html' => ['Rubber production', 'production'], '/content/12.html' => ['Rubber gardens', 'gardens'],
        '/content/13.html' => ['Natural mineral water', 'natural-mineral-water'],
        '/content/24.html' => ['Chittagong Warehouses Ltd. (CWL)', 'cwl'], '/content/25.html' => ['Octavius Steel & Company Ltd.', 'octavius'],
        '/content/27.html' => ['United Insurance Company Ltd. (UIC)', 'uic'], '/content/28.html' => ['United Finance Limited (UFL)', 'ufl'],
        '/content/4.html' => ['Camellia Plc', 'camellia-plc'], '/content/18.html' => ['CDF Orchid Project', 'cdf-orchid-project'],
    ];

    /** @var array<string, Page> legacy href => page */
    private array $pages = [];

    public function run(LegacyImporter $importer, SavePage $savePage, SaveMenu $saveMenu, LegacyHtml $html, SettingsRepository $settings): void
    {
        $dir = storage_path('app/legacy');
        $manifestFile = $dir.'/manifest.json';

        if (! is_file($manifestFile)) {
            $this->command->error('No crawl found. Run: python3 tools/legacy/crawl.py');

            return;
        }
        if (Page::count() > 0 && app()->isProduction() && ! config('app.legacy_reset')) {
            $this->command->error('Content already exists on production. Set LEGACY_RESET=1 to replace it (this deletes all current content).');

            return;
        }

        $manifest = json_decode(file_get_contents($manifestFile), true, flags: JSON_THROW_ON_ERROR);
        $this->call([RolesAndPermissionsSeeder::class, DocumentCategorySeeder::class]);

        $user = User::role('super-admin')->first() ?? User::first();
        if (! $user) {
            $this->command->error('Create an admin user first (set ADMIN_SEED_EMAIL / ADMIN_SEED_PASSWORD and run db:seed).');

            return;
        }

        $this->wipeContent();
        $importer->importAssets($manifest, $dir.'/files', $user);

        $this->settings($settings, $manifest);
        $this->structuredData($manifest);
        $this->pages($manifest, $importer, $savePage, $html, $user);
        $this->documentsAndNews($manifest, $user);
        $this->menus($saveMenu, $manifest);

        Cache::flush();
        ResponseCache::clear();

        $this->command->info(Page::count().' pages, '.Estate::count().' estates, '.TimelineEvent::count().' timeline events published from the old site. Draft legal pages still need their text.');
    }

    // ---- settings -------------------------------------------------------------------------------------

    /** @param array<string, mixed> $manifest */
    private function settings(SettingsRepository $s, array $manifest): void
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) ($manifest['contact']['text'] ?? '')) ?: [])));
        $addressLines = [];
        foreach (array_slice($lines, 1) as $line) {
            if (preg_match('/^(tel|telephone|fax|phone|fields)/i', $line)) {
                break;
            }
            $addressLines[] = $line;
        }
        $address = implode("\n", $addressLines);
        $field = function (string $label) use ($lines): ?string {
            foreach ($lines as $line) {
                if (preg_match('/^'.$label.'/i', $line)) {
                    return trim((string) preg_replace('/^'.$label.'[a-z]*:?\s*/i', '', $line));
                }
            }

            return null;
        };

        foreach ([
            ['identity', 'site_name', 'Duncan Brothers (Bangladesh) Limited'],
            ['identity', 'tagline', 'Tea, rubber and natural mineral water in Bangladesh since 1859'],
            ['identity', 'group_name', 'Camellia Plc'],
            ['identity', 'group_url', 'https://www.camellia.plc.uk'],
            ['contact', 'address_dhaka', $address],
            ['contact', 'phone', $field('tel')],
            ['contact', 'fax', $field('fax')],
            ['seo', 'default_description', Str::limit((string) ($manifest['home']['intro'] ?? ''), 160, '')],
        ] as [$group, $key, $value]) {
            if (filled($value)) {
                $s->set($group, $key, $value);
            }
        }
    }

    // ---- businesses, estates, timeline -----------------------------------------------------------------

    /** @param array<string, mixed> $manifest */
    private function structuredData(array $manifest): void
    {
        $pages = [];
        foreach ($manifest['pages'] as $row) {
            $pages[$row['href']] = $row;
        }
        $estateNames = function (string $href) use ($pages): array {
            $text = strip_tags(str_replace(['</p>', '<br>', '<br />', '</li>', '</h4>'], "\n", (string) ($pages[$href]['body_html'] ?? '')));
            $names = [];
            foreach (preg_split('/\R/', $text) ?: [] as $line) {
                $line = trim(html_entity_decode(str_replace("\xC2\xA0", ' ', $line)));
                if (str_ends_with($line, 'Tea Estate')) {
                    $names[] = trim(Str::beforeLast($line, 'Tea Estate'));
                }
            }

            return $names;
        };

        $tea = Business::create(['name' => 'Tea', 'slug' => 'tea', 'sort_order' => 1,
            'summary' => 'Tea remains our core business: 16 gardens in the Moulvibazar and Habiganj districts of the greater Sylhet region, producing CTC tea.',
            'body' => '<p>Duncan Brothers produces 12–13 million kg of tea annually from 9,100 hectares under tea in its 16 gardens, with an average yield above the national average.</p>',
            'metrics' => [['label' => 'Gardens', 'value' => '16'], ['label' => 'Grant area', 'value' => '18,500 ha'], ['label' => 'Tea a year', 'value' => '12–13 million kg']]]);
        Business::create(['name' => 'Rubber', 'slug' => 'rubber', 'sort_order' => 2,
            'summary' => 'Rubber is grown as an additional crop on land not suited to tea, in 13 of our 16 gardens, and processed at a central factory at Lungla.',
            'body' => '<p>The Group has 1,394 hectares under mature rubber producing over 800 metric tons of smoked sheet a year.</p>',
            'metrics' => [['label' => 'Mature rubber', 'value' => '1,394 ha'], ['label' => 'Production', 'value' => '800+ tonnes a year']]]);
        Business::create(['name' => 'Natural mineral water', 'slug' => 'natural-mineral-water', 'sort_order' => 3,
            'summary' => "Duncan's natural mineral water, produced and marketed by Duncan Products Ltd. at two plants.",
            'body' => '<p>Sold in 500 ml and 1,500 ml bottles and 20-litre jars.</p>',
            'metrics' => [['label' => 'Plants', 'value' => '2'], ['label' => 'Certification', 'value' => 'ISO 22000']]]);

        $rubber = array_map(fn ($n) => Str::lower($n), $estateNames('/content/12.html'));
        foreach ($estateNames('/content/9.html') as $i => $name) {
            $grows = in_array(Str::lower($name), $rubber, true);
            Estate::create(['business_id' => $tea->id, 'name' => $name, 'slug' => Str::slug($name), 'sort_order' => $i,
                'body' => '<p>'.e($name).' Tea Estate is one of the Group\'s 16 gardens in the greater Sylhet region.'.($grows ? ' Rubber is also grown here as an additional crop.' : '').'</p>']);
        }

        foreach ([
            ['1858', 'Walter Duncan sails for Calcutta', 'A Scotsman from Glasgow, Walter Duncan sails from Southampton for Calcutta on 20 November 1858 to start business in India.'],
            ['1859', 'The firm begins in Calcutta', 'Walter Duncan starts a cotton business in January 1859 with Patrick Playfair as Messrs Playfair, Duncan and Company.'],
            ['1860', 'The first tea shares', 'Shares are bought in the Doloo tea garden, and the long journey of Duncans in tea begins.'],
            ['1875', 'Duncan Brothers & Company', 'Walter and William Duncan start on their own account: Walter Duncan and Co in Glasgow and Duncan Brothers & Company in Calcutta.'],
            ['1890', 'Twelve gardens', 'Tea has become the major business. The agency now has twelve gardens with a planted area of 2,350 hectares.'],
            ['1910', 'The trio-estates', 'A controlling interest is acquired in Allynugger Company and the estates Madhabpur, Patrakhola and Kurma.'],
            ['1923', 'About 60 estates', 'The agency grows to about 60 estates, 20,000 planted hectares and a crop of 14.5 million kg a year. The company is incorporated in December.'],
            ['1948', 'A public company', 'The company is converted into a public company on 31 July 1948.'],
            ['1949', 'Duncan Brothers (Pakistan) Ltd.', 'A new company is formed on 20 January 1949 with its head office in Chittagong.'],
            ['1950', 'Chittagong Warehouses', 'The new Chittagong tea warehouse of 65,000 square feet opens for business in January 1950.'],
            ['1970', 'Head office moves to Dhaka', 'The head office moves from Chittagong to Dhaka in January 1970.'],
            ['1971', 'Rebuilding after independence', 'After the Liberation War Duncan Brothers (Bangladesh) Ltd. takes up the challenge of rebuilding. During 1971–74 the Lawrie Group assumes the controlling interest and the company becomes part of Camellia Plc.'],
            ['1983', 'Rubber', 'Rubber is introduced as an additional crop at Lungla and Allynugger Tea Estates.'],
            ['1985', 'United Insurance Company', 'The Group diversifies. United Insurance Company Ltd. commences business on 15 October 1985.'],
            ['1989', 'Primary Health Care and United Leasing', 'The Primary Health Care programme is launched at Lungla, the first of its kind in the private sector. United Leasing begins its journey.'],
            ['1992', 'Surma Valley Tea Company', 'The Surma Valley Tea Company with Luskerpore, Silloah and Rajkie is acquired.'],
            ['1994', 'Hospital and Duncan Products', 'The 50-bed Camellia Duncan Foundation Hospital is inaugurated on 26 March 1994. Duncan Products Ltd. begins producing natural mineral water.'],
            ['1996', 'Yasser Life Guard', 'The life-saving service at Cox\'s Bazar is inaugurated on 23 October 1996.'],
            ['2001', 'Sixteen gardens', 'With Chaklapunji the Group owns 16 gardens.'],
            ['2004', 'Camellia Duncan Foundation School', 'The residential secondary school for workers\' children is inaugurated on 26 March 2004.'],
            ['2006', 'Orchid project', 'The Foundation\'s orchid project begins at Shahbazpur near Brahmanbaria.'],
            ['2014', 'United Finance Limited', 'United Leasing re-brands as United Finance Limited.'],
        ] as $i => [$year, $title, $body]) {
            TimelineEvent::create(['year' => $year, 'title' => $title, 'body' => $body, 'sort_order' => $i]);
        }
    }

    // ---- pages ----------------------------------------------------------------------------------------

    /** @param array<string, mixed> $manifest */
    private function pages(array $manifest, LegacyImporter $importer, SavePage $save, LegacyHtml $html, User $user): void
    {
        $byHref = [];
        foreach ($manifest['pages'] as $row) {
            $byHref[$row['href']] = $row;
        }
        $items = fn (array $files) => array_values(array_filter(array_map(fn ($f) => ($id = $importer->mediaId($f)) ? ['media_id' => $id] : null, $files)));
        $make = function (string $title, string $slug, ?Page $parent, array $blocks, string $source) use ($save, $user) {
            $page = $save(null, ['title' => $title, 'slug' => $slug, 'parent_id' => $parent?->id, 'blocks' => $blocks], $user, "Imported from the old website: $source");
            $page->update(['status' => 'published', 'published_at' => now()]);

            return $page;
        };

        foreach ($manifest['nav'] as $group) {
            $section = null;
            $made = [];

            foreach ($group['items'] as $i => $item) {
                $href = $item['href'];
                if (str_starts_with($href, '/gallery/')) {
                    continue;
                }
                $legacy = $byHref[$href];
                [$title, $slug] = self::TITLES[$href] ?? [$i === 0 ? $group['title'] : $item['title'], null];
                if ($i === 0) {
                    $title = $group['title'] === 'Associate companies' ? 'Associate companies' : $group['title'];
                    $slug = Str::slug($group['title']);
                }
                $parent = $i === 0 ? null : ($item['parent'] !== null ? ($made[$item['parent']] ?? $section) : $section);

                $clean = $html->clean((string) $legacy['body_html'], $legacy['title']);
                $blocks = [];
                $photos = self::PHOTOS[$href] ?? [];

                if (! empty($photos['hero'])) {
                    $blocks[] = ['type' => 'hero', 'data' => ['heading' => $title, 'intro' => null, 'slides' => $items($photos['hero'])]];
                }

                $body = match ($href) {
                    '/content/9.html' => $this->estateList('Tea estates', null),
                    '/content/12.html' => $this->estateList('Rubber is grown at', Estate::where('body', 'like', '%Rubber is also grown%')->pluck('name')->all()),
                    default => $clean['html'],
                };
                if ($body !== '') {
                    $blocks[] = ['type' => 'rich_text', 'data' => ['heading' => null, 'body' => $body]];
                }
                if ($href === '/content/3.html') {
                    $blocks[] = ['type' => 'timeline', 'data' => ['items' => []]];
                }
                if (! empty($photos['gallery'])) {
                    $blocks[] = ['type' => 'gallery', 'data' => ['heading' => 'Photographs', 'items' => $items($photos['gallery'])]];
                }

                $page = $make($title, $slug ?: Str::slug($title), $parent, $blocks, $href);
                $this->pages[$href] = $page;
                $made[$i] = $page;
                $section ??= $page;
            }

            // Section landing pages list their children as tiles.
            if ($section) {
                $children = Page::where('parent_id', $section->id)->orderBy('id')->get();
                if ($children->isNotEmpty()) {
                    $section->update(['blocks' => [...$section->blocks, ['type' => 'quick_links', 'data' => ['items' => $children->map(fn ($c) => ['label' => $c->title, 'url' => '/'.$c->path])->take(5)->all()]]]]);
                }
            }
        }

        // Tea and Rubber link to their sub-pages.
        foreach (['/content/7.html', '/content/10.html'] as $href) {
            $page = $this->pages[$href];
            $kids = Page::where('parent_id', $page->id)->get();
            $page->update(['blocks' => [...$page->blocks, ['type' => 'quick_links', 'data' => ['items' => $kids->map(fn ($c) => ['label' => $c->title, 'url' => '/'.$c->path])->all()]]]]);
        }

        $gallery = [];
        foreach ($manifest['gallery'] as $album) {
            if ($album['images']) {
                $gallery[] = ['type' => 'gallery', 'data' => ['heading' => $album['category'], 'items' => $items($album['images'])]];
            }
        }
        $this->pages['gallery'] = $make('Gallery', 'gallery', null, $gallery, '/gallery/index.html');

        $home = [
            ['type' => 'hero', 'data' => ['heading' => 'Duncan Brothers (Bangladesh) Limited', 'intro' => 'Tea, rubber and natural mineral water in Bangladesh since 1859', 'slides' => $items(['1486449130_tea_garden.jpg', '1486445013_plucking_2.jpg', '1486437415_rubber_garden.jpg', '1486390358_duncans_water.jpg', '1486449341_cdf_school.jpg'])]],
            ['type' => 'quick_links', 'data' => ['items' => [['label' => 'About Duncan', 'url' => '/about-duncan'], ['label' => 'Products', 'url' => '/products'], ['label' => 'Corporate responsibility', 'url' => '/corporate-responsibility'], ['label' => 'Associate companies', 'url' => '/associate-companies'], ['label' => 'Gallery', 'url' => '/gallery']]]],
            ['type' => 'rich_text', 'data' => ['heading' => null, 'body' => '<p>'.e($manifest['home']['intro']).'</p>']],
            ['type' => 'stats', 'data' => ['items' => [['value' => '1859', 'label' => 'the year Duncan Brothers began'], ['value' => '18,500', 'label' => 'people employed (approx.)'], ['value' => '12–13', 'label' => 'million kg of tea a year']]]],
            ['type' => 'cards_grid', 'data' => ['heading' => 'Our products', 'items' => [
                ['title' => 'Tea', 'text' => 'CTC tea from 16 gardens in the greater Sylhet region.', 'media_id' => $importer->mediaId('1486444944_plucking_1.jpg'), 'url' => '/products/tea'],
                ['title' => 'Rubber', 'text' => 'Smoked sheet rubber, grown alongside tea.', 'media_id' => $importer->mediaId('1486437447_tapping_(female_worker).jpg'), 'url' => '/products/rubber'],
                ['title' => 'Natural mineral water', 'text' => "Duncan's water, bottled by Duncan Products Ltd.", 'media_id' => $importer->mediaId('1486390358_duncans_water.jpg'), 'url' => '/products/natural-mineral-water'],
            ]]],
            ['type' => 'news_feed', 'data' => ['heading' => 'Latest news & information', 'limit' => 3]],
            ['type' => 'document_list', 'data' => ['heading' => 'Annual report', 'category' => 'annual-reports', 'limit' => 3]],
            ['type' => 'cta_band', 'data' => ['heading' => 'More than 160 years of history', 'text' => 'From a cotton and tea business in Calcutta to a leading group in Bangladesh.', 'button_label' => 'Read our history', 'button_url' => '/about-duncan/history-of-duncan']],
        ];
        $this->pages['home'] = $make('Home', 'home', null, $home, '/index.html');

        // The old site has no legal texts: leave drafts for the Company Secretary instead of inventing any.
        foreach (['privacy-notice' => 'Privacy notice', 'cookie-policy' => 'Cookie policy', 'terms-of-use' => 'Terms of use', 'accessibility' => 'Accessibility statement'] as $slug => $title) {
            $page = $save(null, ['title' => $title, 'slug' => $slug, 'blocks' => [['type' => 'rich_text', 'data' => ['heading' => null, 'body' => '<p>[To be written and approved by the Company Secretary / Legal before launch.]</p>']]]], $user, 'Placeholder: no legal text exists on the old website');
            $page->update(['status' => 'draft']);
        }
    }

    /** @param list<string>|null $only */
    private function estateList(string $heading, ?array $only): string
    {
        $estates = Estate::orderBy('sort_order')->get()->when($only !== null, fn ($c) => $c->filter(fn ($e) => in_array($e->name, $only, true)));
        $items = $estates->map(fn ($e) => '<li><a href="/what-we-do/tea/estates/'.$e->slug.'">'.e($e->name).' Tea Estate</a></li>')->implode('');

        return '<p>'.e($heading).':</p><ul>'.$items.'</ul>';
    }

    // ---- documents, news ---------------------------------------------------------------------------------

    /** @param array<string, mixed> $manifest */
    private function documentsAndNews(array $manifest, User $user): void
    {
        Document::query()->update(['status' => 'published', 'published_at' => now()]);

        $news = $manifest['home']['news'][0] ?? null;
        if ($news) {
            $category = NewsCategory::create(['name' => 'Group', 'slug' => 'group']);
            Post::create(['type' => 'press', 'category_id' => $category->id, 'title' => $news['title'], 'slug' => Str::slug($news['title']),
                'excerpt' => 'The Annual Report 2025 is available to download.',
                'body' => '<p>'.e($news['title']).'.</p><p>The Annual Report 2025 can be downloaded from the <a href="/documents/annual-reports">annual reports</a> page.</p>',
                'tags' => ['annual report'], 'status' => 'published', 'published_at' => now(), 'author_id' => $user->id]);
        }
    }

    // ---- menus --------------------------------------------------------------------------------------------

    /** @param array<string, mixed> $manifest */
    private function menus(SaveMenu $save, array $manifest): void
    {
        $items = [];
        foreach ($manifest['nav'] as $group) {
            $section = $this->pages[$group['items'][0]['href']];
            $items[] = ['label' => $group['title'], 'page_id' => $section->id, 'is_active' => true];
            $parent = count($items) - 1;
            foreach (array_slice($group['items'], 1) as $item) {
                if ($item['parent'] === null && isset($this->pages[$item['href']])) {
                    $items[] = ['label' => $this->pages[$item['href']]->title, 'page_id' => $this->pages[$item['href']]->id, 'parent' => $parent, 'is_active' => true];
                }
            }
        }
        $items[] = ['label' => 'Gallery', 'page_id' => $this->pages['gallery']->id, 'is_active' => true];
        $items[] = ['label' => 'News', 'url' => '/news', 'is_active' => true];
        $items[] = ['label' => 'Contact', 'url' => '/contact', 'is_active' => true];
        $save(MenuLocation::Explore, $items);

        $save(MenuLocation::Footer, [
            ['label' => 'Annual reports', 'url' => '/documents/annual-reports', 'is_active' => true],
            ['label' => 'News', 'url' => '/news', 'is_active' => true],
            ['label' => 'Gallery', 'page_id' => $this->pages['gallery']->id, 'is_active' => true],
            ['label' => 'Contact', 'url' => '/contact', 'is_active' => true],
        ]);
    }
}
