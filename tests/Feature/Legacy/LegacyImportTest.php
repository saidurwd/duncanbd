<?php

use App\Domain\Cms\Enums\MenuLocation;
use App\Domain\Cms\Legacy\LegacyHtml;
use App\Domain\Cms\MenuRepository;
use App\Domain\Cms\Models\Page;
use App\Domain\Documents\Models\Document;
use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use App\Domain\Media\Models\MediaItem;
use App\Domain\Settings\SettingsRepository;
use Database\Seeders\DocumentCategorySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, DocumentCategorySeeder::class]);
    User::factory()->create()->assignRole(Role::SuperAdmin->value);
    Storage::fake('local');
    Storage::fake('public');
    config(['media-library.queue_conversions_by_default' => false]);

    // A tiny fake crawl: nav tree, one readable page, one unreadable, two gallery images, one PDF.
    $this->dir = sys_get_temp_dir().'/legacy-'.uniqid();
    File::ensureDirectoryExists($this->dir.'/files');
    foreach (['1486449199_factory.jpg', '1486437447_tapping_(female_worker).jpg'] as $name) {
        $img = imagecreatetruecolor(40, 30);
        imagejpeg($img, $this->dir.'/files/'.$name);
    }
    file_put_contents($this->dir.'/files/Annual_Report_2025.pdf', "%PDF-1.4\n%x\n");

    $manifest = [
        'nav' => [
            ['title' => 'About Duncan', 'items' => [
                ['title' => 'About Duncan', 'href' => '/content/2.html', 'parent' => null],
                ['title' => 'History of Duncan', 'href' => '/content/3.html', 'parent' => null],
            ]],
            ['title' => 'Products', 'items' => [
                ['title' => 'Products', 'href' => '/content/6.html', 'parent' => null],
                ['title' => 'Tea', 'href' => '/content/7.html', 'parent' => null],
                ['title' => 'Garden', 'href' => '/content/9.html', 'parent' => 1],
                ['title' => 'Gallery', 'href' => '/gallery/category/1.html', 'parent' => 1],
            ]],
            ['title' => 'Corporate Responsibility', 'items' => [
                ['title' => 'Responsibility', 'href' => '/content/14.html', 'parent' => null],
            ]],
        ],
        'pages' => [
            ['href' => '/content/2.html', 'title' => 'About Duncan', 'status' => 'ok', 'images' => [],
                'body_html' => '<h1 style="color:red">About</h1><p>&nbsp;</p><p class="x">We grow <strong>tea</strong>.</p><h4><a href="/content/3.html"><input type="button" value="Our history"></a></h4><script>x()</script><img src="/uploads/banners/1486449199_factory.jpg">'],
            ['href' => '/content/3.html', 'title' => 'History of Duncan', 'status' => 'login_required'],
            ['href' => '/content/6.html', 'title' => 'Products', 'status' => 'login_required'],
            ['href' => '/content/7.html', 'title' => 'Tea', 'status' => 'login_required'],
            ['href' => '/content/9.html', 'title' => 'Garden', 'status' => 'login_required'],
            ['href' => '/content/14.html', 'title' => 'Responsibility', 'status' => 'login_required'],
        ],
        'gallery' => [['category' => 'Tea', 'source' => '/gallery/category/1.html', 'images' => ['1486449199_factory.jpg', '1486437447_tapping_(female_worker).jpg']]],
        'documents' => [['file' => 'Annual_Report_2025.pdf', 'title' => 'Annual Report 2025']],
        'home' => ['intro' => 'We are proud to be a large tea producer.', 'news' => [['title' => '42nd AGM 2025', 'files' => ['Annual_Report_2025.pdf']]]],
        'contact' => ['text' => "Duncan Brothers (Bangladesh) Limited\n\nCamellia House\n22, Kazi Nazrul Islam Avenue\nDhaka - 1000\nBangladesh\nTelephone: 88-02-58611720-2\nFax: 88-02-58613576\nFields with * are required."],
    ];
    file_put_contents($this->dir.'/manifest.json', json_encode($manifest));
});

afterEach(fn () => File::deleteDirectory($this->dir));

function runImport(): void
{
    test()->artisan('legacy:import', ['--path' => test()->dir])->assertSuccessful();
}

it('imports pages as unpublished drafts following the legacy navigation', function () {
    runImport();

    expect(Page::where('status', '!=', 'draft')->count())->toBe(0)
        ->and(Page::pluck('path')->sort()->values()->all())->toBe([
            'about-duncan', 'about-duncan/history-of-duncan', 'corporate-responsibility', 'gallery', 'home',
            'products', 'products/tea', 'products/tea/gallery', 'products/tea/garden',
        ])
        ->and(Page::where('path', 'products/tea/garden')->first()->parent->path)->toBe('products/tea');
});

it('converts readable legacy HTML and attaches page images as a gallery', function () {
    runImport();

    $about = Page::where('path', 'about-duncan')->firstOrFail();
    $body = $about->blocks[0]['data']['body'];

    expect($about->blocks[0]['type'])->toBe('rich_text')
        ->and($body)->toContain('<h2>About</h2>')->toContain('<strong>tea</strong>')->toContain('<a href="/content/3.html">Our history</a>')
        ->not->toContain('script')->not->toContain('style=')->not->toContain('&nbsp;')->not->toContain('<input')
        ->and($about->blocks[1]['type'])->toBe('gallery')
        ->and($about->blocks[1]['data']['items'])->toHaveCount(1);
});

it('keeps structure for unreadable pages and flags them for an editor', function () {
    runImport();

    $history = Page::where('path', 'about-duncan/history-of-duncan')->firstOrFail();

    expect($history->blocks[0]['data']['body'])->toContain('CONTENT TO MIGRATE')->toContain('/content/3.html')
        ->and(Page::where('path', 'corporate-responsibility')->value('title'))->toBe('Corporate Responsibility');   // group label, not "Responsibility"
});

it('imports gallery images into folders with titles, alt text and private originals', function () {
    runImport();

    $item = MediaItem::where('title', 'Tapping (female worker)')->firstOrFail();

    expect(MediaItem::count())->toBe(2)
        ->and($item->alt_text)->toBe('Tapping (female worker)')
        ->and($item->folder->name)->toBe('Tea')
        ->and($item->getFirstMedia('file')->file_name)->not->toContain('tapping');
    expect(Page::where('path', 'products/tea/gallery')->first()->blocks[0]['data']['items'])->toHaveCount(2);
});

it('imports the PDF as a draft annual report, never public', function () {
    runImport();

    $doc = Document::with('currentVersion', 'category')->firstOrFail();

    expect($doc->status->value)->toBe('draft')->and($doc->category->slug)->toBe('annual-reports')->and($doc->financial_year)->toBe('2025')
        ->and($doc->description)->toBe('42nd AGM 2025')->and($doc->currentVersion->version)->toBe(1);
    Storage::disk('local')->assertExists($doc->currentVersion->path);
    expect($doc->isLive())->toBeFalse();
});

it('fills empty settings from the old site without overwriting existing ones', function () {
    app(SettingsRepository::class)->set('identity', 'site_name', 'Already set');

    runImport();

    $settings = app(SettingsRepository::class);
    expect($settings->get('identity', 'site_name'))->toBe('Already set')
        ->and($settings->get('contact', 'address_dhaka'))->toBe("Camellia House\n22, Kazi Nazrul Islam Avenue\nDhaka - 1000\nBangladesh")
        ->and($settings->get('contact', 'phone'))->toBe('88-02-58611720-2')
        ->and($settings->get('identity', 'group_name'))->toBe('Camellia Plc');
});

it('builds a two-level Explore menu from the old navigation', function () {
    runImport();

    $tree = app(MenuRepository::class)->tree(MenuLocation::Explore);
    $labels = array_column($tree, 'label');

    expect($labels)->toContain('About Duncan', 'Products', 'Corporate Responsibility', 'Gallery', 'Contact us')
        ->and(collect($tree)->firstWhere('label', 'Products')['children'][0]['label'])->toBe('Tea')
        ->and(collect($tree)->firstWhere('label', 'Products')['children'])->toHaveCount(1);   // Garden/Gallery stay on the Tea page (two levels max)
});

it('is idempotent: a second run creates nothing new', function () {
    runImport();
    $counts = [Page::count(), MediaItem::count(), Document::count()];

    runImport();

    expect([Page::count(), MediaItem::count(), Document::count()])->toBe($counts);
});

it('never publishes anything or exposes imported content publicly', function () {
    runImport();

    $this->get('/about-duncan')->assertNotFound();
    $this->get('/documents/annual-reports/annual-report-2025')->assertNotFound();
});

it('fails clearly without a manifest', function () {
    $this->artisan('legacy:import', ['--path' => 'storage/app/does-not-exist'])->assertFailed();
});

it('cleans legacy HTML', function () {
    $out = app(LegacyHtml::class)->clean('<h5 align="center">Small</h5><p>&nbsp;</p><hr><p><font color="red">Text</font></p><iframe src="x"></iframe><img src="/uploads/banners/a%20b.jpg">');

    expect($out['html'])->toBe('<h4>Small</h4><p>Text</p>')->and($out['images'])->toBe(['a b.jpg']);
});
