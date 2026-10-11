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
use App\Domain\Identity\Models\User;
use App\Domain\Media\Models\MediaItem;
use App\Domain\News\Models\Post;
use App\Domain\People\Models\Person;
use App\Domain\People\Models\TimelineEvent;
use App\Domain\Settings\SettingsRepository;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

/**
 * A believable, fully PUBLISHED demo site for QA, UAT and performance runs. Never for production.
 * All names, figures and text are invented placeholders.
 *
 *   php artisan db:seed --class=QaDemoSeeder
 */
class QaDemoSeeder extends Seeder
{
    public function run(SavePage $savePage, SaveMenu $saveMenu, StoreDocumentVersion $storeVersion, SettingsRepository $settings): void
    {
        if (app()->isProduction()) {
            $this->command->error('Refusing to seed demo data in production.');

            return;
        }

        $this->call([RolesAndPermissionsSeeder::class, DocumentCategorySeeder::class]);

        $user = User::role('super-admin')->first() ?? User::factory()->create(['name' => 'QA Admin', 'email' => 'qa@example.com']);

        foreach ([
            ['identity', 'site_name', 'Duncan Brothers (Bangladesh) Ltd'], ['identity', 'group_name', 'Camellia Plc'], ['identity', 'group_url', 'https://www.camellia.plc.uk'],
            ['contact', 'email', 'info@example.com'], ['contact', 'phone', '+880 2 0000 0000'], ['contact', 'address_dhaka', "Camellia House\n22 Kazi Nazrul Islam Avenue\nDhaka 1000"],
            ['social', 'linkedin', 'https://www.linkedin.com/company/example'],
        ] as [$group, $key, $value]) {
            $settings->set($group, $key, $value);
        }

        // Media: generated "photographs" (gradient + noise) so page weight is realistic.
        $media = [];
        foreach (['Tea garden at dawn', 'Plucking season', 'Rubber tapping', 'Chattogram warehouse', 'Estate school', 'Factory floor'] as $i => $title) {
            $media[] = $this->image($title, $i, $user)->id;
        }

        // Structured content
        $tea = Business::create(['name' => 'Tea', 'slug' => 'tea', 'summary' => 'CTC teas from our estates in Sylhet and Chattogram.', 'body' => '<p>Demo text about tea production.</p>', 'metrics' => [['label' => 'Factories', 'value' => '14'], ['label' => 'Hectares', 'value' => '9,000']], 'sort_order' => 1]);
        Business::create(['name' => 'Rubber', 'slug' => 'rubber', 'summary' => 'Ribbed smoked sheet from two processing plants.', 'sort_order' => 2]);
        foreach (['Kodala', 'Karimpur', 'Deanston'] as $i => $name) {
            Estate::create(['business_id' => $tea->id, 'name' => $name, 'slug' => strtolower($name), 'district' => 'Sylhet', 'area_ha' => 500 + $i * 120, 'factory' => "$name Factory", 'certifications' => ['Rainforest Alliance'], 'photo_media_id' => $media[$i], 'sort_order' => $i]);
        }
        foreach ([['Alex Example', 'Chair', 'board'], ['Sam Sample', 'Managing Director', 'leadership'], ['Jo Placeholder', 'Director, Finance', 'leadership']] as $i => [$name, $role, $group]) {
            Person::create(['name' => $name, 'slug' => strtolower(str_replace(' ', '-', $name)), 'position' => $role, 'group' => $group, 'bio' => '<p>Demo biography.</p>', 'sort_order' => $i]);
        }
        foreach ([['1859', 'Founded'], ['1960', 'Expansion into rubber'], ['2026', 'New Chattogram warehouse']] as $i => [$year, $title]) {
            TimelineEvent::create(['year' => $year, 'title' => $title, 'body' => 'Demo milestone.', 'sort_order' => $i]);
        }

        // Documents (valid minimal PDFs)
        $dir = storage_path('app/qa');
        File::ensureDirectoryExists($dir);
        foreach ([['Annual Report 2025', 'annual-reports', '2025'], ['Annual Report 2024', 'annual-reports', '2024'], ['Notice of AGM 2025', 'agm-egm-notices', '2025'], ['Code of Conduct', 'policies', null]] as [$title, $cat, $year]) {
            $file = $dir.'/'.str($title)->slug().'.pdf';
            file_put_contents($file, "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n".str_repeat("demo\n", 500));
            $doc = Document::create(['category_id' => DocumentCategory::where('slug', $cat)->value('id'), 'title' => $title, 'slug' => str($title)->slug(), 'financial_year' => $year, 'document_date' => now()->subMonths(2), 'visibility' => 'public', 'status' => 'published', 'published_at' => now()->subMonth(), 'created_by' => $user->id]);
            $storeVersion($doc, new UploadedFile($file, basename($file), 'application/pdf', null, true), $user);
        }

        foreach (range(1, 6) as $i) {
            Post::create(['type' => $i % 3 === 0 ? 'press' : 'news', 'title' => "Demo story number $i", 'slug' => "demo-story-$i", 'excerpt' => 'A short summary of the demo story.', 'body' => '<p>Demo body text. '.str_repeat('Lorem ipsum dolor sit amet. ', 20).'</p>', 'featured_media_id' => $media[$i % 6], 'status' => 'published', 'published_at' => now()->subDays($i * 9), 'author_id' => $user->id]);
        }
        Vacancy::create(['title' => 'Assistant Estate Manager', 'slug' => 'assistant-estate-manager', 'department' => 'Operations', 'location' => 'Sylhet', 'employment_type' => 'full_time', 'description' => '<p>Demo role description.</p>', 'closing_date' => now()->addMonth(), 'status' => 'published', 'published_at' => now()->subWeek()]);

        // Pages
        $block = fn (string $type, array $data) => ['type' => $type, 'data' => $data];
        $slides = array_map(fn ($id) => ['media_id' => $id], array_slice($media, 0, 3));
        $make = function (string $title, string $path, array $blocks) use ($savePage, $user) {
            $parent = str_contains($path, '/') ? Page::where('path', dirname($path))->first() : null;
            $page = $savePage(null, ['title' => $title, 'slug' => basename($path), 'parent_id' => $parent?->id, 'blocks' => $blocks], $user, 'QA demo');
            $page->update(['status' => 'published', 'published_at' => now()->subDay()]);

            return $page;
        };

        $make('Home', 'home', [
            $block('hero', ['heading' => 'Rooted in Bangladesh since 1859', 'intro' => 'Tea, rubber and warehousing.', 'slides' => $slides]),
            $block('quick_links', ['items' => [['label' => 'What we do', 'url' => '/what-we-do'], ['label' => 'Our people', 'url' => '/who-we-are'], ['label' => 'Investors', 'url' => '/investors'], ['label' => 'Sustainability', 'url' => '/sustainability']]]),
            $block('document_list', ['heading' => 'Investor updates', 'category' => 'annual-reports', 'limit' => 3]),
            $block('stats', ['items' => [['value' => '14', 'label' => 'tea factories'], ['value' => '2', 'label' => 'rubber plants'], ['value' => '9,000 ha', 'label' => 'under management']]]),
            $block('rich_text', ['heading' => 'Our purpose', 'body' => '<p>Demo purpose statement with <strong>quality</strong>, <strong>long term</strong> and <strong>integrity</strong>.</p>']),
            $block('video', ['heading' => 'Our story', 'youtube_id' => 'dQw4w9WgXcQ']),
            $block('news_feed', ['heading' => 'News', 'limit' => 3]),
        ]);
        $make('Who we are', 'who-we-are', [$block('rich_text', ['heading' => 'About us', 'body' => '<p>Demo.</p>']), $block('people_grid', ['heading' => 'Leadership', 'group' => null]), $block('timeline', ['items' => []])]);
        $make('What we do', 'what-we-do', [$block('company_accordion', ['items' => []]), $block('gallery', ['heading' => 'Gallery', 'items' => array_map(fn ($id) => ['media_id' => $id], $media)])]);
        $make('Investors', 'investors', [$block('document_list', ['heading' => 'Annual reports', 'category' => 'annual-reports', 'limit' => 10]), $block('document_list', ['heading' => 'AGM notices', 'category' => 'agm-egm-notices', 'limit' => 10])]);
        $make('Sustainability', 'sustainability', [$block('rich_text', ['heading' => 'Sustainability', 'body' => '<p>Demo.</p>']), $block('accordion_faq', ['heading' => 'FAQ', 'items' => [['question' => 'What certifications do you hold?', 'answer' => '<p>Demo.</p>']]])]);
        foreach (['privacy-notice' => 'Privacy notice', 'cookie-policy' => 'Cookie policy', 'terms-of-use' => 'Terms of use', 'accessibility' => 'Accessibility statement'] as $slug => $title) {
            $make($title, $slug, [$block('rich_text', ['heading' => $title, 'body' => '<p>Demo.</p>'])]);
        }

        $page = fn (string $path) => Page::where('path', $path)->value('id');
        $saveMenu(MenuLocation::Explore, [
            ['label' => 'Who we are', 'page_id' => $page('who-we-are'), 'is_active' => true],
            ['label' => 'What we do', 'page_id' => $page('what-we-do'), 'is_active' => true],
            ['label' => 'Tea', 'url' => '/what-we-do/tea', 'parent' => 1, 'is_active' => true],
            ['label' => 'Sustainability', 'page_id' => $page('sustainability'), 'is_active' => true],
            ['label' => 'Investors', 'page_id' => $page('investors'), 'is_active' => true],
            ['label' => 'News', 'url' => '/news', 'is_active' => true],
            ['label' => 'Careers', 'url' => '/careers/vacancies', 'is_active' => true],
            ['label' => 'Contact', 'url' => '/contact', 'is_active' => true],
        ]);
        $saveMenu(MenuLocation::Legal, array_map(fn ($p) => ['label' => Page::where('path', $p)->value('title'), 'page_id' => $page($p), 'is_active' => true], ['privacy-notice', 'cookie-policy', 'terms-of-use', 'accessibility']));
    }

    private function image(string $title, int $seed, User $user): MediaItem
    {
        $w = 2400;
        $h = 1350;
        $img = imagecreatetruecolor($w, $h);
        mt_srand($seed + 7);
        for ($y = 0; $y < $h; $y += 6) {
            $c = imagecolorallocate($img, 30 + ($y * 60 / $h) + $seed * 12, 90 + ($y * 80 / $h), 50 + $seed * 10);
            imagefilledrectangle($img, 0, $y, $w, $y + 6, $c);
        }
        for ($i = 0; $i < 4000; $i++) {
            imagesetpixel($img, mt_rand(0, $w - 1), mt_rand(0, $h - 1), imagecolorallocate($img, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)));
        }
        $path = storage_path('app/qa/img-'.$seed.'.jpg');
        File::ensureDirectoryExists(dirname($path));
        imagejpeg($img, $path, 82);

        $item = MediaItem::create(['title' => $title, 'alt_text' => $title, 'uploaded_by' => $user->id]);
        $item->addMedia($path)->preservingOriginal()->usingFileName("demo-$seed.jpg")->toMediaCollection(MediaItem::COLLECTION);

        return $item;
    }
}
