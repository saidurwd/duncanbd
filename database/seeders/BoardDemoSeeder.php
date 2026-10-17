<?php

namespace Database\Seeders;

use App\Domain\Business\Models\Business;
use App\Domain\Business\Models\Estate;
use App\Domain\Careers\Models\Vacancy;
use App\Domain\Cms\Actions\SaveMenu;
use App\Domain\Cms\Actions\SavePage;
use App\Domain\Cms\Enums\MenuLocation;
use App\Domain\Cms\Models\Page;
use App\Domain\Documents\Actions\StoreDocumentVersion;
use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Models\DocumentCategory;
use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use App\Domain\Media\Models\MediaFolder;
use App\Domain\Media\Models\MediaItem;
use App\Domain\News\Models\NewsCategory;
use App\Domain\News\Models\Post;
use App\Domain\People\Models\Person;
use App\Domain\People\Models\TimelineEvent;
use App\Domain\Settings\SettingsRepository;
use Database\Seeders\Concerns\WipesContent;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Spatie\ResponseCache\Facades\ResponseCache;

/**
 * A complete, presentable, fully PUBLISHED site for a board or management demonstration.
 *
 * Real material: the photographs and Annual Report 2025 crawled from the old duncanbd.com (tools/legacy/crawl.py),
 * the business description, group names and the associate-company list from that site.
 * Illustrative material, deliberately labelled "sample" on the page: people, estates other than Karimpore,
 * news stories other than the AGM item, vacancies, policy documents. Figures are limited to the two stated
 * publicly (14 tea estates, 2 rubber processing plants). Replace everything before launch.
 *
 * It REPLACES all content (pages, media, news, documents, people, estates, vacancies, menus, settings) and keeps users.
 *
 *   php artisan db:seed --class=BoardDemoSeeder
 */
class BoardDemoSeeder extends Seeder
{
    use WipesContent;

    /** @var array<string, int> legacy file name => media item id */
    private array $m = [];

    public function run(SavePage $savePage, SaveMenu $saveMenu, StoreDocumentVersion $storeVersion, SettingsRepository $settings): void
    {
        if (app()->isProduction()) {
            $this->command->error('Refusing to seed demo data in production.');

            return;
        }

        $files = storage_path('app/legacy/files');
        if (! is_dir($files)) {
            $this->command->error('Photos not found. Run: python3 tools/legacy/crawl.py');

            return;
        }

        // Create the resized WebP versions now: with a queued driver and no worker running they would never exist
        // and pages would serve the full-size originals.
        config(['media-library.queue_conversions_by_default' => false]);

        $this->call([RolesAndPermissionsSeeder::class, DocumentCategorySeeder::class]);
        $this->wipeContent();

        $admin = User::firstOrCreate(
            ['email' => 'board-demo@example.com'],
            ['name' => 'Demo Administrator', 'password' => 'Board-Demo-2026-Pass!', 'is_active' => true],
        );
        $admin->syncRoles(Role::SuperAdmin->value);

        $this->settings($settings);
        $this->media($files, $admin);
        $this->business();
        $this->people();
        $this->documents($files, $admin, $storeVersion);
        $this->news($admin);
        $this->vacancies();
        $this->pages($savePage, $admin);
        $this->menus($saveMenu);

        Cache::flush();
        ResponseCache::clear();

        $this->command->info('Board demo ready. Admin: board-demo@example.com / Board-Demo-2026-Pass! (set up 2FA at first sign-in)');
    }

    // ---- settings -----------------------------------------------------------------------------------

    private function settings(SettingsRepository $s): void
    {
        foreach ([
            ['identity', 'site_name', 'Duncan Brothers (Bangladesh) Limited'],
            ['identity', 'tagline', 'Tea, rubber and natural mineral water from Bangladesh'],
            ['identity', 'group_name', 'Camellia Plc'],
            ['identity', 'group_url', 'https://www.camellia.plc.uk'],
            ['contact', 'phone', '88-02-58611720-2, 88-02-9661397-8'],
            ['contact', 'email', 'info@duncanbd.com'],
            ['contact', 'address_dhaka', "Camellia House\n22, Kazi Nazrul Islam Avenue\nDhaka - 1000\nBangladesh"],
            ['social', 'linkedin', 'https://www.linkedin.com/'],
            ['footer', 'footer_text', 'Demonstration preview with sample content. A member of the Camellia Group.'],
            ['seo', 'default_description', 'Duncan Brothers (Bangladesh) Limited: one of the largest private tea producers in Bangladesh, with rubber processing and natural mineral water.'],
            ['enquiries', 'route_general', 'info@duncanbd.com'],
            ['enquiries', 'route_investor', 'investors@duncanbd.com'],
            ['enquiries', 'hr_email', 'hr@duncanbd.com'],
        ] as [$group, $key, $value]) {
            $s->set($group, $key, $value);
        }
    }

    // ---- media --------------------------------------------------------------------------------------

    private function media(string $dir, User $admin): void
    {
        $albums = [
            'Tea' => ['1486439110_nursery_1.jpg' => 'Tea seedlings in a nursery', '1486442537_nursery_2.jpg' => 'Young tea plants in the nursery',
                '1486444602_section_with_new_shade_sets.jpg' => 'A tea section planted with new shade trees', '1486444944_plucking_1.jpg' => 'Plucking tea leaves by hand',
                '1486445013_plucking_2.jpg' => 'Pluckers working along the tea rows', '1486445125_plucking_3.jpg' => 'Tea plucking in the estate',
                '1486449130_tea_garden.jpg' => 'A tea garden in Bangladesh', '1486449199_factory.jpg' => 'Inside a tea factory', '1486449232_karimpore_factory.jpg' => 'Karimpore tea factory'],
            'Rubber' => ['1486437415_rubber_garden.jpg' => 'A rubber garden', '1486437447_tapping_(female_worker).jpg' => 'A worker tapping a rubber tree',
                '1486437496_tapping_(male_worker).jpg' => 'Tapping rubber latex', '1486437525_rubber_collection.jpg' => 'Collecting rubber latex', '1486437574_milling_machine.jpg' => 'Rubber milling machine',
                '1486437614_rss_sheet_rolled_in_milling_machine.jpg' => 'Rubber sheet rolled in the milling machine', '1486437638_rss_sheet_after_cogulation.jpg' => 'Rubber sheet after coagulation',
                '1486438781_rss_sheet_ready_to_be_smoked.jpg' => 'Ribbed sheets ready to be smoked', '1486438478_raw_materials.jpg' => 'Raw rubber materials', '1486437967_rss.jpg' => 'Ribbed smoked sheet rubber'],
            'Duncan Products' => ['1486390267_dpl_factory.jpg' => 'The Duncan Products factory', '1486390292_dpl_labeling.jpg' => 'Bottle labelling at Duncan Products', '1486390316_dpl_quality_test.jpg' => 'Quality testing of mineral water',
                '1486390358_duncans_water.jpg' => "Duncan's natural mineral water", '1486390484_orchid_(pink).jpg' => 'Pink orchids', '1486390594_orchid_(purple).jpg' => 'Purple orchids',
                '1486390804_orchid_(yellow).jpg' => 'Yellow orchids', '1486391126_orchid_garden.jpg' => 'The orchid garden'],
            'Community' => ['1486449341_cdf_school.jpg' => 'The CDF school', '1486449381_cdfs_students.jpg' => 'Students at the CDF school', '1486449410_cdfs_playground.jpg' => 'The school playground',
                '1486449433_cdf_hospital.jpg' => 'The CDF hospital', '1486449469_diagnostics.jpg' => 'Diagnostics at the hospital', '1486449503_cdfh_ot.jpg' => 'Hospital operating theatre',
                '1486449570_cdf_hospital_ward.jpg' => 'A hospital ward', '1486449595_cdfh_ward.jpg' => 'Patient ward', '1486449615_clinic.jpg' => 'Community clinic'],
            'Awards' => ['cwl.jpg' => 'Certificate awarded to CWL', 'db_vat.jpg' => 'VAT award certificate'],
        ];

        foreach ($albums as $album => $images) {
            $folder = MediaFolder::create(['name' => $album]);
            foreach ($images as $file => $alt) {
                if (! is_file("$dir/$file")) {
                    continue;
                }
                $item = MediaItem::create(['folder_id' => $folder->id, 'title' => $alt, 'alt_text' => $alt, 'credit' => 'Duncan Brothers (Bangladesh) Limited', 'uploaded_by' => $admin->id]);
                $item->addMedia($this->enhanced("$dir/$file"))->usingFileName(Str::random(24).'.jpg')->usingName($alt)->toMediaCollection(MediaItem::COLLECTION);
                $this->m[$file] = $item->id;
            }
        }
    }

    /** The old photos are 640 px wide: upscale (bicubic) and lightly sharpen so full-width heroes look acceptable. */
    private function enhanced(string $path): string
    {
        $src = imagecreatefromjpeg($path);
        [$w, $h] = [imagesx($src), imagesy($src)];
        if ($w >= 1600 || $h > $w) {
            return $path;
        }
        $big = imagescale($src, 1920, (int) round($h * 1920 / $w), IMG_BICUBIC);
        imageconvolution($big, [[0, -1, 0], [-1, 5, -1], [0, -1, 0]], 1, 0);
        $out = storage_path('app/legacy/enhanced');
        File::ensureDirectoryExists($out);
        $dest = $out.'/'.basename($path);
        imagejpeg($big, $dest, 88);

        return $dest;
    }

    /** @return list<array{media_id: int}> */
    private function items(string ...$files): array
    {
        return array_values(array_map(fn ($f) => ['media_id' => $this->m[$f]], array_filter($files, fn ($f) => isset($this->m[$f]))));
    }

    // ---- structured content ---------------------------------------------------------------------------

    private function business(): void
    {
        $tea = Business::create(['name' => 'Tea', 'slug' => 'tea', 'sort_order' => 1,
            'summary' => 'One of the largest and best-known private tea producers in Bangladesh: 14 tea estates producing high-quality CTC teas.',
            'body' => '<p>Our teas are sold mainly through the local auctions in Bangladesh, principally Chittagong.</p>',
            'metrics' => [['label' => 'Tea estates', 'value' => '14'], ['label' => 'Tea type', 'value' => 'CTC']], 'logo_media_id' => $this->m['1486449130_tea_garden.jpg'] ?? null]);
        Business::create(['name' => 'Rubber', 'slug' => 'rubber', 'sort_order' => 2,
            'summary' => 'Two rubber processing plants producing ribbed smoked sheet (RSS), supplied directly to local buyers.',
            'body' => '<p>Rubber products are supplied directly to local buyers across the region.</p>',
            'metrics' => [['label' => 'Processing plants', 'value' => '2'], ['label' => 'Product', 'value' => 'Ribbed smoked sheet']]]);
        Business::create(['name' => 'Natural mineral water', 'slug' => 'natural-mineral-water', 'sort_order' => 3,
            'summary' => "Duncan's natural mineral water, bottled by Duncan Products Ltd.", 'body' => '<p>Quality-tested and bottled at the Duncan Products factory.</p>']);

        Estate::create(['business_id' => $tea->id, 'name' => 'Karimpore', 'slug' => 'karimpore', 'factory' => 'Karimpore tea factory', 'photo_media_id' => $this->m['1486449232_karimpore_factory.jpg'] ?? null,
            'body' => '<p>Karimpore is one of our tea factories.</p>', 'sort_order' => 1]);
        foreach (['Sample Estate A' => 'sample-estate-a', 'Sample Estate B' => 'sample-estate-b'] as $name => $slug) {
            Estate::create(['business_id' => $tea->id, 'name' => $name, 'slug' => $slug, 'district' => 'To be confirmed', 'certifications' => ['Certification to be confirmed'],
                'body' => '<p>Sample entry: estate details (location, area, factory, certifications, map pin) are managed here and shown on the public site.</p>', 'sort_order' => 2]);
        }
    }

    private function people(): void
    {
        foreach ([['Chairman', 'Sample Name', 'board'], ['Non-executive Director', 'Sample Name', 'board'], ['Managing Director', 'Sample Name', 'leadership'], ['Director, Finance', 'Sample Name', 'leadership']] as $i => [$role, $name, $group]) {
            Person::create(['name' => "$name ($role)", 'slug' => 'sample-'.($i + 1), 'position' => $role, 'group' => $group, 'sort_order' => $i,
                'bio' => '<p>Sample entry. Names, photographs and biographies are managed in the admin.</p>']);
        }
        TimelineEvent::create(['year' => '1859', 'title' => 'Rooted in Bangladesh', 'body' => 'Our heritage in the region begins.', 'sort_order' => 1]);
        TimelineEvent::create(['year' => '2026', 'title' => 'A new digital home', 'body' => 'This website, managed by our own team.', 'sort_order' => 2]);
    }

    private function documents(string $dir, User $admin, StoreDocumentVersion $store): void
    {
        $cat = fn (string $slug) => DocumentCategory::where('slug', $slug)->value('id');
        $sample = storage_path('app/legacy/sample.pdf');
        file_put_contents($sample, "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\nSample document for demonstration.\n");

        foreach ([
            ['Annual Report 2025', 'annual-reports', '2025', "$dir/Annual_Report_2025.pdf", '42nd Eastland Camellia Limited (ECL) Annual General Meeting 2025'],
            ['Notice of AGM 2025 (sample)', 'agm-egm-notices', '2025', $sample, 'Sample notice to show how AGM documents are published.'],
            ['Code of Conduct (sample)', 'policies', null, $sample, 'Sample policy document.'],
            ['Modern Slavery Statement (sample)', 'policies', null, $sample, 'Sample policy document.'],
            ['Sustainability Report 2025 (sample)', 'sustainability-reports', '2025', $sample, 'Sample report.'],
        ] as $i => [$title, $category, $year, $path, $desc]) {
            if (! is_file($path)) {
                continue;
            }
            $doc = Document::create(['category_id' => $cat($category), 'title' => $title, 'slug' => Str::slug($title), 'description' => $desc, 'financial_year' => $year,
                'document_date' => now()->subMonths($i + 1), 'visibility' => 'public', 'status' => 'published', 'published_at' => now()->subMonths($i + 1), 'created_by' => $admin->id,
                'cover_media_id' => $i === 0 ? ($this->m['1486449130_tea_garden.jpg'] ?? null) : null]);
            $store($doc, new UploadedFile($path, basename($path), 'application/pdf', null, true), $admin);
        }
    }

    private function news(User $admin): void
    {
        $group = NewsCategory::create(['name' => 'Group', 'slug' => 'group']);
        $community = NewsCategory::create(['name' => 'Community', 'slug' => 'community']);
        $operations = NewsCategory::create(['name' => 'Our operations', 'slug' => 'our-operations']);

        $stories = [
            ['42nd Eastland Camellia Limited Annual General Meeting 2025', 'press', $group, '1486449199_factory.jpg',
                'The Annual Report 2025 is now available to download from our investors page.',
                '<p>The Annual Report 2025 for the 42nd Annual General Meeting of Eastland Camellia Limited is published on our investors page.</p><p>Shareholders can download the report as a PDF. Questions can be sent to our Company Secretary through the contact form.</p>'],
            ['From nursery to garden: how a tea plant begins', 'news', $operations, '1486439110_nursery_1.jpg',
                'Every tea bush starts life in a nursery, where young plants are raised under shade before they move to the field.',
                '<p>Tea plants are raised in nurseries before they are planted out in the garden. Young plants are kept under shade and cared for until they are strong enough to be transplanted into prepared sections of the estate.</p><h3>Why shade matters</h3><p>Shade trees protect young plants from harsh sun and help keep the soil healthy. Newly planted sections are often laid out with shade trees from the start.</p><ul><li>Seedlings are raised and watched closely in the nursery</li><li>Sections are prepared and planted with new shade trees</li><li>Plants are cared for until they are ready for their first plucking</li></ul>'],
            ['Plucking: the hands behind every cup', 'news', $operations, '1486444944_plucking_1.jpg',
                'Fine plucking by hand is the first step of quality in a cup of tea.',
                '<p>Tea leaves are plucked by hand, section by section, throughout the season. The leaf is taken to the factory the same day, where it is processed into CTC tea.</p><h3>What CTC means</h3><p>CTC stands for crush, tear, curl: the withered leaf is cut and curled into small, uniform granules that brew quickly and strongly.</p>'],
            ['How raw latex becomes a ribbed smoked sheet', 'news', $operations, '1486438781_rss_sheet_ready_to_be_smoked.jpg',
                'Rubber tapping, coagulation, milling and smoking: the journey of a ribbed smoked sheet.',
                '<p>Rubber starts with tapping: a thin cut in the bark of the tree lets latex flow into a collecting cup. The latex is gathered and brought to the processing plant.</p><h3>From latex to sheet</h3><ol><li>Latex is collected and strained</li><li>It is coagulated into a soft slab</li><li>The slab is rolled through the milling machine into thin ribbed sheets</li><li>The sheets are dried and smoked, giving ribbed smoked sheet (RSS)</li></ol>'],
            ['Quality testing at Duncan Products', 'news', $group, '1486390316_dpl_quality_test.jpg',
                'Natural mineral water is tested before it is bottled, labelled and sent out.',
                '<p>Duncan Products Ltd. bottles Duncan\'s natural mineral water. Samples are tested in the quality laboratory as part of the bottling process, from the factory line through to the labelled bottle.</p>'],
            ['Care close to home: the CDF hospital and school', 'news', $community, '1486449433_cdf_hospital.jpg',
                'Health care and education for the communities around our estates.',
                '<p>Alongside our estates and factories we support a hospital and a school for the surrounding communities, together with a community clinic.</p><p>Photographs of the wards, diagnostics, classrooms and playground are in our gallery.</p>'],
            ['The orchid garden at Duncan Products', 'news', $community, '1486391126_orchid_garden.jpg',
                'Beyond tea and rubber: a garden of orchids in bloom.',
                '<p>The CDF Orchid Project grows orchids under shade nets. Pink, purple and yellow varieties are shown in our gallery.</p>'],
            ['A look inside a tea factory', 'news', $operations, '1486449232_karimpore_factory.jpg',
                'Karimpore is one of our tea factories, where the day\'s leaf is turned into tea.',
                '<p>Inside the factory, the freshly plucked leaf is withered, cut, fermented and dried. Karimpore factory is one of the places where this happens.</p>'],
        ];

        foreach ($stories as $n => [$title, $type, $category, $img, $excerpt, $body]) {
            Post::create(['type' => $type, 'category_id' => $category->id, 'title' => $title, 'slug' => Str::slug($title), 'excerpt' => $excerpt,
                'featured_media_id' => $this->m[$img] ?? null, 'body' => $body, 'tags' => $n === 0 ? ['investors', 'agm'] : ['operations'],
                'status' => 'published', 'published_at' => now()->subDays(8 + $n * 17), 'author_id' => $admin->id]);
        }
    }

    private function vacancies(): void
    {
        foreach ([['Assistant Estate Manager', 'Operations', 'Sylhet'], ['Factory Engineer', 'Engineering', 'Chattogram'], ['Management Trainee', 'Graduate programme', 'Dhaka']] as $i => [$title, $dept, $loc]) {
            Vacancy::create(['title' => "$title (sample)", 'slug' => Str::slug($title), 'department' => $dept, 'location' => $loc, 'employment_type' => $i === 2 ? 'graduate' : 'full_time',
                'description' => '<p>Sample vacancy to demonstrate online applications with CV upload and an HR pipeline.</p>', 'requirements' => '<ul><li>Sample requirement</li></ul>',
                'closing_date' => now()->addMonths(2), 'status' => 'published', 'published_at' => now()->subDays(3)]);
        }
    }

    // ---- pages ----------------------------------------------------------------------------------------

    private function pages(SavePage $save, User $admin): void
    {
        $b = fn (string $type, array $data) => ['type' => $type, 'data' => $data];
        $make = function (string $title, string $path, array $blocks) use ($save, $admin) {
            $parent = str_contains($path, '/') ? Page::where('path', dirname($path))->first() : null;
            $page = $save(null, ['title' => $title, 'slug' => basename($path), 'parent_id' => $parent?->id, 'blocks' => $blocks], $admin, 'Board demo');
            $page->update(['status' => 'published', 'published_at' => now()->subDay()]);
        };
        $text = fn (string $h, string $body) => $b('rich_text', ['heading' => $h, 'body' => $body]);

        $make('Home', 'home', [
            $b('hero', ['heading' => 'Rooted in Bangladesh', 'intro' => 'Tea, rubber and natural mineral water, grown and made with care.',
                'slides' => $this->items('1486449130_tea_garden.jpg', '1486445013_plucking_2.jpg', '1486437415_rubber_garden.jpg', '1486390358_duncans_water.jpg', '1486449341_cdf_school.jpg')]),
            $b('quick_links', ['items' => [['label' => 'What we do', 'url' => '/what-we-do'], ['label' => 'Who we are', 'url' => '/who-we-are'], ['label' => 'Investors', 'url' => '/investors'], ['label' => 'Sustainability', 'url' => '/sustainability'], ['label' => 'Careers', 'url' => '/careers']]]),
            $text('', "<p>At Duncan Brothers, we are proud to be one of the largest and well-known private tea producers in Bangladesh. Our operations span 14 tea estates that produce high-quality CTC teas. We also have two rubber processing plants. The majority of our teas are sold through Bangladesh's local auctions, principally Chittagong. Our rubber products are supplied directly to local buyers across the region.</p>"),
            $b('stats', ['items' => [['value' => '14', 'label' => 'tea estates'], ['value' => '2', 'label' => 'rubber processing plants'], ['value' => '1859', 'label' => 'rooted in Bangladesh since']]]),
            $b('document_list', ['heading' => 'Investor updates', 'category' => 'annual-reports', 'limit' => 3]),
            $b('cards_grid', ['heading' => 'What we do', 'items' => [
                ['title' => 'Tea', 'text' => 'CTC teas from 14 estates.', 'media_id' => $this->m['1486444944_plucking_1.jpg'] ?? null, 'url' => '/what-we-do/tea'],
                ['title' => 'Rubber', 'text' => 'Ribbed smoked sheet from two plants.', 'media_id' => $this->m['1486437447_tapping_(female_worker).jpg'] ?? null, 'url' => '/what-we-do/rubber'],
                ['title' => 'Natural mineral water', 'text' => "Duncan's, bottled by Duncan Products Ltd.", 'media_id' => $this->m['1486390358_duncans_water.jpg'] ?? null, 'url' => '/what-we-do/natural-mineral-water'],
            ]]),
            $b('news_feed', ['heading' => 'Latest news & information', 'limit' => 3]),
            $b('cta_band', ['heading' => 'Build a career with us', 'text' => 'See our current vacancies and apply online.', 'button_label' => 'Working with us', 'button_url' => '/careers']),
        ]);

        $make('Who we are', 'who-we-are', [
            $text('About Duncan Brothers', '<p>Duncan Brothers (Bangladesh) Limited is one of the largest and well-known private tea producers in Bangladesh, with rubber processing and natural mineral water. We are a member of the Camellia Group.</p>'),
            $b('timeline', ['items' => []]),
            $b('people_grid', ['heading' => 'Board and leadership (sample entries)', 'group' => null]),
            $b('cta_band', ['heading' => 'Part of the Camellia Group', 'text' => 'Camellia Plc is our parent group.', 'button_label' => 'Visit Camellia Plc', 'button_url' => 'https://www.camellia.plc.uk']),
        ]);

        $make('What we do', 'what-we-do', [
            $text('Our businesses', '<p>Tea, rubber and natural mineral water, supported by associate companies in warehousing, property, insurance and leasing.</p>'),
            $b('company_accordion', ['items' => []]),
            $b('gallery', ['heading' => 'Gallery', 'items' => $this->items('1486444944_plucking_1.jpg', '1486449199_factory.jpg', '1486437447_tapping_(female_worker).jpg', '1486437574_milling_machine.jpg', '1486390316_dpl_quality_test.jpg', '1486391126_orchid_garden.jpg', '1486449381_cdfs_students.jpg', '1486444602_section_with_new_shade_sets.jpg')]),
        ]);
        $steps = fn (array $rows) => $b('cards_grid', ['heading' => null, 'items' => $rows]);

        $make('Tea', 'what-we-do/tea', [
            $b('hero', ['heading' => 'Tea', 'intro' => 'One of the largest and best-known private tea producers in Bangladesh.', 'slides' => $this->items('1486449130_tea_garden.jpg', '1486444602_section_with_new_shade_sets.jpg', '1486445125_plucking_3.jpg')]),
            $text('CTC teas from 14 estates', '<p>Our operations span 14 tea estates that produce high-quality CTC teas. The majority of our teas are sold through Bangladesh\'s local auctions, principally Chittagong.</p><p>CTC stands for crush, tear, curl: the leaf is cut and curled into small, uniform granules that brew quickly and strongly.</p>'),
            $b('stats', ['items' => [['value' => '14', 'label' => 'tea estates'], ['value' => 'CTC', 'label' => 'tea type'], ['value' => 'Chittagong', 'label' => 'principal auction centre']]]),
            $steps([
                ['title' => 'Nursery', 'text' => 'Young plants are raised under shade before being planted out.', 'media_id' => $this->m['1486439110_nursery_1.jpg'] ?? null],
                ['title' => 'Planting', 'text' => 'New sections are planted with shade trees.', 'media_id' => $this->m['1486444602_section_with_new_shade_sets.jpg'] ?? null],
                ['title' => 'Plucking', 'text' => 'Leaf is plucked by hand across the season.', 'media_id' => $this->m['1486444944_plucking_1.jpg'] ?? null],
                ['title' => 'The factory', 'text' => 'The day\'s leaf is processed into tea.', 'media_id' => $this->m['1486449199_factory.jpg'] ?? null],
            ]),
            $b('cards_grid', ['heading' => 'Our estates', 'items' => [
                ['title' => 'Karimpore', 'text' => 'Karimpore tea factory.', 'media_id' => $this->m['1486449232_karimpore_factory.jpg'] ?? null, 'url' => '/what-we-do/tea/estates/karimpore'],
                ['title' => 'Sample Estate A', 'text' => 'Sample entry for the estate directory.', 'url' => '/what-we-do/tea/estates/sample-estate-a'],
                ['title' => 'Sample Estate B', 'text' => 'Sample entry for the estate directory.', 'url' => '/what-we-do/tea/estates/sample-estate-b'],
            ]]),
            $b('gallery', ['heading' => 'Tea gallery', 'items' => $this->items('1486439110_nursery_1.jpg', '1486442537_nursery_2.jpg', '1486444944_plucking_1.jpg', '1486445013_plucking_2.jpg', '1486445125_plucking_3.jpg', '1486449130_tea_garden.jpg')]),
            $b('cta_band', ['heading' => 'Tea in the news', 'text' => 'Stories from our gardens and factories.', 'button_label' => 'Read the news', 'button_url' => '/news']),
        ]);

        $make('Rubber', 'what-we-do/rubber', [
            $b('hero', ['heading' => 'Rubber', 'intro' => 'Two processing plants producing ribbed smoked sheet.', 'slides' => $this->items('1486437415_rubber_garden.jpg', '1486437447_tapping_(female_worker).jpg', '1486437574_milling_machine.jpg')]),
            $text('From latex to ribbed smoked sheet', '<p>We have two rubber processing plants. Our rubber products are supplied directly to local buyers across the region.</p><p>Ribbed smoked sheet (RSS) is made by coagulating latex, rolling it into thin ribbed sheets and smoking them.</p>'),
            $b('stats', ['items' => [['value' => '2', 'label' => 'rubber processing plants'], ['value' => 'RSS', 'label' => 'ribbed smoked sheet'], ['value' => 'Direct', 'label' => 'supplied to local buyers']]]),
            $steps([
                ['title' => 'Tapping', 'text' => 'Latex is collected from the trees.', 'media_id' => $this->m['1486437447_tapping_(female_worker).jpg'] ?? null],
                ['title' => 'Collection', 'text' => 'Latex is gathered and brought to the plant.', 'media_id' => $this->m['1486437525_rubber_collection.jpg'] ?? null],
                ['title' => 'Coagulation', 'text' => 'Latex sets into soft slabs.', 'media_id' => $this->m['1486437638_rss_sheet_after_cogulation.jpg'] ?? null],
                ['title' => 'Milling', 'text' => 'Slabs are rolled into thin ribbed sheets.', 'media_id' => $this->m['1486437614_rss_sheet_rolled_in_milling_machine.jpg'] ?? null],
                ['title' => 'Smoking', 'text' => 'Sheets are dried and smoked.', 'media_id' => $this->m['1486438781_rss_sheet_ready_to_be_smoked.jpg'] ?? null],
            ]),
            $b('gallery', ['heading' => 'Rubber gallery', 'items' => $this->items('1486437415_rubber_garden.jpg', '1486437496_tapping_(male_worker).jpg', '1486437574_milling_machine.jpg', '1486438478_raw_materials.jpg', '1486437967_rss.jpg')]),
        ]);

        $make('Natural mineral water', 'what-we-do/natural-mineral-water', [
            $b('hero', ['heading' => 'Natural mineral water', 'intro' => "Duncan's, bottled by Duncan Products Ltd.", 'slides' => $this->items('1486390358_duncans_water.jpg', '1486390267_dpl_factory.jpg')]),
            $text('Tested, labelled, delivered', "<p>Duncan Products Ltd. bottles Duncan's natural mineral water. Samples are tested in the quality laboratory, and bottles are labelled on the production line before they leave the factory.</p>"),
            $steps([
                ['title' => 'The factory', 'text' => 'Where the water is bottled.', 'media_id' => $this->m['1486390267_dpl_factory.jpg'] ?? null],
                ['title' => 'Labelling', 'text' => 'Bottles are labelled on the line.', 'media_id' => $this->m['1486390292_dpl_labeling.jpg'] ?? null],
                ['title' => 'Quality testing', 'text' => 'Samples are tested in the laboratory.', 'media_id' => $this->m['1486390316_dpl_quality_test.jpg'] ?? null],
            ]),
            $b('gallery', ['heading' => 'The CDF Orchid Project', 'items' => $this->items('1486390484_orchid_(pink).jpg', '1486390594_orchid_(purple).jpg', '1486390804_orchid_(yellow).jpg', '1486391126_orchid_garden.jpg')]),
        ]);

        $make('Sustainability', 'sustainability', [
            $text('Looking after land and people', '<p>Good tea and rubber depend on healthy land and strong communities. Our approach covers the plantations themselves and the people who live and work around them.</p>'),
            $b('cards_grid', ['heading' => 'Our focus areas', 'items' => [
                ['title' => 'Environment', 'text' => 'New sections are planted with shade trees that protect young plants and help keep the soil healthy.', 'media_id' => $this->m['1486444602_section_with_new_shade_sets.jpg'] ?? null],
                ['title' => 'Health care', 'text' => 'A hospital and community clinic serve the communities around our estates.', 'media_id' => $this->m['1486449503_cdfh_ot.jpg'] ?? null],
                ['title' => 'Education', 'text' => 'The CDF school, its classrooms and playground.', 'media_id' => $this->m['1486449410_cdfs_playground.jpg'] ?? null],
            ]]),
            $b('document_list', ['heading' => 'Sustainability reports', 'category' => 'sustainability-reports', 'limit' => 5]),
            $b('cta_band', ['heading' => 'See it for yourself', 'text' => 'Photographs of our hospital, school and gardens.', 'button_label' => 'Open the gallery', 'button_url' => '/gallery']),
        ]);

        $make('Working at Duncan Brothers', 'careers', [
            $b('hero', ['heading' => 'Work with us', 'intro' => 'Estates, factories, laboratories and offices across Bangladesh.', 'slides' => $this->items('1486444944_plucking_1.jpg', '1486449199_factory.jpg', '1486390316_dpl_quality_test.jpg')]),
            $text('A career in a long-established business', '<p>We are one of the largest private tea producers in Bangladesh, with rubber processing and natural mineral water. Our teams work in the gardens, in the factories, in the laboratory and in the office.</p><p>Look at our current vacancies and apply online with your CV.</p>'),
            $b('cta_band', ['heading' => 'Current vacancies', 'text' => 'Apply online in a few minutes.', 'button_label' => 'View vacancies', 'button_url' => '/careers/vacancies']),
        ]);

        $make('Associate companies', 'associate-companies', [
            $b('cards_grid', ['heading' => 'Associate companies', 'items' => [
                ['title' => 'Duncan Products Ltd.', 'text' => "Bottles Duncan's natural mineral water."], ['title' => 'CWL', 'text' => 'Warehousing in Chattogram.', 'media_id' => $this->m['cwl.jpg'] ?? null],
                ['title' => 'Octavius Duncan Properties Ltd', 'text' => 'Property.'], ['title' => 'UIC', 'text' => 'Insurance.'], ['title' => 'UFL', 'text' => 'Leasing and finance.']]]),
        ]);

        $make('Investors', 'investors', [
            $text('', '<p>Annual reports, AGM notices and statutory disclosures.</p>'),
            $b('featured_document', ['heading' => 'Latest annual report', 'document_id' => Document::where('slug', 'annual-report-2025')->value('id')]),
            $b('document_list', ['heading' => 'Annual reports', 'category' => 'annual-reports', 'limit' => 10]),
            $b('document_list', ['heading' => 'AGM & EGM notices', 'category' => 'agm-egm-notices', 'limit' => 10]),
            $b('cta_band', ['heading' => 'Shareholder enquiries', 'text' => 'Contact our Company Secretary.', 'button_label' => 'Contact investor relations', 'button_url' => '/contact?category=investor']),
        ]);

        $make('Corporate responsibility', 'corporate-responsibility', [
            $text('', '<p>Beyond our estates and factories we support health care and education in the communities around us, through the CDF hospital and school and other social commitments.</p>'),
            $b('cards_grid', ['heading' => 'Our commitments', 'items' => [
                ['title' => 'Primary health care', 'text' => 'The CDF hospital and community clinic.', 'media_id' => $this->m['1486449433_cdf_hospital.jpg'] ?? null],
                ['title' => 'Education', 'text' => 'The CDF school.', 'media_id' => $this->m['1486449381_cdfs_students.jpg'] ?? null],
                ['title' => 'CDF Orchid Project', 'text' => 'An orchid garden and nursery.', 'media_id' => $this->m['1486391126_orchid_garden.jpg'] ?? null]]]),
            $b('gallery', ['heading' => 'Hospital and school', 'items' => $this->items('1486449341_cdf_school.jpg', '1486449410_cdfs_playground.jpg', '1486449469_diagnostics.jpg', '1486449570_cdf_hospital_ward.jpg', '1486449615_clinic.jpg')]),
            $b('document_list', ['heading' => 'Sustainability reports', 'category' => 'sustainability-reports', 'limit' => 5]),
            $b('accordion_faq', ['heading' => 'Frequently asked questions', 'items' => [['question' => 'Where can I read the policies?', 'answer' => '<p>Policies are published as documents; this is a sample answer.</p>']]]),
        ]);

        $make('Gallery', 'gallery', [
            $b('gallery', ['heading' => 'Tea', 'items' => $this->items('1486439110_nursery_1.jpg', '1486444944_plucking_1.jpg', '1486445125_plucking_3.jpg', '1486449130_tea_garden.jpg', '1486449199_factory.jpg', '1486449232_karimpore_factory.jpg')]),
            $b('gallery', ['heading' => 'Rubber', 'items' => $this->items('1486437415_rubber_garden.jpg', '1486437496_tapping_(male_worker).jpg', '1486437525_rubber_collection.jpg', '1486437614_rss_sheet_rolled_in_milling_machine.jpg', '1486437967_rss.jpg')]),
            $b('gallery', ['heading' => 'Duncan Products and orchids', 'items' => $this->items('1486390267_dpl_factory.jpg', '1486390292_dpl_labeling.jpg', '1486390484_orchid_(pink).jpg', '1486390804_orchid_(yellow).jpg')]),
        ]);

        foreach (['privacy-notice' => 'Privacy notice', 'cookie-policy' => 'Cookie policy', 'terms-of-use' => 'Terms of use', 'accessibility' => 'Accessibility statement'] as $slug => $title) {
            $make($title, $slug, [$text($title, '<p>Sample legal text. The final wording must be provided and approved by the Company Secretary and Legal before launch.</p>')]);
        }
    }

    private function menus(SaveMenu $save): void
    {
        $id = fn (string $path) => Page::where('path', $path)->value('id');
        $save(MenuLocation::Explore, [
            ['label' => 'Who we are', 'page_id' => $id('who-we-are'), 'is_active' => true],
            ['label' => 'What we do', 'page_id' => $id('what-we-do'), 'is_active' => true],
            ['label' => 'Tea', 'url' => '/what-we-do/tea', 'parent' => 1, 'is_active' => true],
            ['label' => 'Rubber', 'url' => '/what-we-do/rubber', 'parent' => 1, 'is_active' => true],
            ['label' => 'Natural mineral water', 'url' => '/what-we-do/natural-mineral-water', 'parent' => 1, 'is_active' => true],
            ['label' => 'Associate companies', 'page_id' => $id('associate-companies'), 'parent' => 1, 'is_active' => true],
            ['label' => 'Sustainability', 'page_id' => $id('sustainability'), 'is_active' => true],
            ['label' => 'Corporate responsibility', 'page_id' => $id('corporate-responsibility'), 'is_active' => true],
            ['label' => 'Investors', 'page_id' => $id('investors'), 'is_active' => true],
            ['label' => 'News & media', 'url' => '/news', 'is_active' => true],
            ['label' => 'Gallery', 'page_id' => $id('gallery'), 'is_active' => true],
            ['label' => 'Careers', 'page_id' => $id('careers'), 'is_active' => true],
            ['label' => 'Contact', 'url' => '/contact', 'is_active' => true],
        ]);
        $save(MenuLocation::Footer, [
            ['label' => 'Investors', 'page_id' => $id('investors'), 'is_active' => true], ['label' => 'News', 'url' => '/news', 'is_active' => true],
            ['label' => 'Careers', 'url' => '/careers/vacancies', 'is_active' => true], ['label' => 'Contact', 'url' => '/contact', 'is_active' => true],
        ]);
        $save(MenuLocation::Legal, array_map(fn ($p) => ['label' => Page::where('path', $p)->value('title'), 'page_id' => $id($p), 'is_active' => true], ['privacy-notice', 'cookie-policy', 'terms-of-use', 'accessibility']));
    }
}
