<?php

use App\Domain\Business\Models\Business;
use App\Domain\Cms\Actions\SaveMenu;
use App\Domain\Cms\Enums\MenuLocation;
use App\Domain\Cms\Models\Page;
use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Models\DocumentCategory;
use App\Domain\News\Models\Post;
use App\Domain\Settings\SettingsRepository;
use Database\Seeders\DocumentCategorySeeder;

function livePageWith(string $title, string $path, array $blocks = []): Page
{
    return Page::create(['title' => $title, 'slug' => basename($path), 'path' => $path, 'status' => 'published', 'published_at' => now()->subMinute(), 'blocks' => $blocks]);
}

function block(string $type, array $data): array
{
    return ['type' => $type, 'schema_version' => 1, 'data' => $data];
}

it('renders the accessible page shell: skip link, landmarks, one h1, explore dialog', function () {
    $about = livePageWith('About us', 'about', [block('rich_text', ['heading' => 'Hello', 'body' => '<p>x</p>'])]);
    app(SaveMenu::class)(MenuLocation::Explore, [['label' => 'About us', 'page_id' => $about->id, 'is_active' => true]]);

    $html = $this->get('/about')->assertOk()
        ->assertSee('Skip to content')
        ->assertSee('<main id="main">', false)
        ->assertSee('data-explore-toggle', false)
        ->assertSee('role="dialog"', false)
        ->assertSee('aria-expanded="false"', false)
        ->assertSee('aria-label="Breadcrumb"', false)
        ->getContent();

    expect(substr_count($html, '<h1'))->toBe(1)
        ->and($html)->toContain('<a href="/about"');
});

it('never emits inline styles or executable inline scripts (strict CSP)', function () {
    $img = null;
    livePageWith('Home', 'home', [
        block('hero', ['heading' => 'Rooted', 'intro' => 'Hi', 'slides' => []]),
        block('quick_links', ['items' => [['label' => 'Investors', 'url' => '/investors']]]),
        block('stats', ['items' => [['value' => '14', 'label' => 'factories']]]),
        block('video', ['heading' => 'Film', 'youtube_id' => 'abc123']),
        block('gallery', ['heading' => 'Photos', 'items' => []]),
        block('cta_band', ['heading' => 'Join', 'button_label' => 'Go', 'button_url' => '/careers']),
    ]);

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)->not->toMatch('/\sstyle="/i')
        ->and($html)->not->toMatch('/\son(click|load|error)=/i');

    // Only data blocks (JSON-LD) and Vite-served files may be <script>s; none without src may execute.
    preg_match_all('/<script(?![^>]*\bsrc=)(?![^>]*application\/ld\+json)[^>]*>/i', $html, $inline);
    expect($inline[0])->toBe([]);
});

it('click-to-load video has no third-party request until activated', function () {
    livePageWith('Home', 'home', [block('video', ['heading' => 'Film', 'youtube_id' => 'abc123'])]);

    $html = $this->get('/')->getContent();

    expect($html)->toContain('data-video="abc123"')->not->toMatch('/<iframe/i');
});

it('emits canonical, Open Graph and Organization JSON-LD with the parent group', function () {
    $settings = app(SettingsRepository::class);
    $settings->set('identity', 'site_name', 'Duncan Brothers (Bangladesh) Ltd');
    $settings->set('identity', 'group_name', 'Camellia Plc');
    $settings->set('identity', 'group_url', 'https://camellia.plc.uk');
    livePageWith('About us', 'about');

    $this->get('/about')->assertOk()
        ->assertSee('<link rel="canonical" href="'.url('/about').'">', false)
        ->assertSee('property="og:title"', false)
        ->assertSee('"parentOrganization":{"@type":"Organization","name":"Camellia Plc"', false)
        ->assertSee('A Camellia Plc company');
});

it('builds breadcrumbs with BreadcrumbList structured data', function () {
    livePageWith('Who we are', 'who-we-are');
    livePageWith('History', 'who-we-are/history');

    $this->get('/who-we-are/history')->assertOk()
        ->assertSee('Who we are')
        ->assertSee('aria-current="page"', false)
        ->assertSee('BreadcrumbList', false);
});

it('escapes dangerous content inside JSON-LD', function () {
    app(SettingsRepository::class)->set('identity', 'site_name', 'Evil </script><script>alert(1)</script>');
    livePageWith('About us', 'about');

    $html = $this->get('/about')->getContent();

    expect($html)->not->toContain('</script><script>alert(1)');
});

it('shows the cookie banner hidden by default and gates analytics behind consent', function () {
    app(SettingsRepository::class)->set('footer', 'analytics_id', 'G-TEST123');
    livePageWith('About us', 'about');

    $html = $this->get('/about')->getContent();

    expect($html)->toContain('data-cookie-banner')->toContain('hidden')
        ->and($html)->toContain('data-analytics-id="G-TEST123"')
        ->and($html)->not->toContain('googletagmanager.com');   // the loader only runs after the visitor accepts
});

it('sends no cookies on public pages', function () {
    livePageWith('About us', 'about');

    expect($this->get('/about')->headers->getCookies())->toBe([]);
});

it('searches pages, news, documents and businesses and ranks title matches first', function () {
    $this->seed(DocumentCategorySeeder::class);
    livePageWith('Warehouse development', 'warehouse');
    livePageWith('Other page', 'other', [block('rich_text', ['heading' => 'x', 'body' => '<p>mentions the warehouse in passing</p>'])]);
    Post::create(['type' => 'news', 'title' => 'Chattogram warehouse opens', 'slug' => 'w', 'status' => 'published', 'published_at' => now()->subDay(), 'excerpt' => 'New warehouse']);
    Post::create(['type' => 'news', 'title' => 'Draft warehouse', 'slug' => 'd', 'status' => 'draft']);
    Document::create(['category_id' => DocumentCategory::first()->id, 'title' => 'Warehouse policy', 'slug' => 'wp', 'visibility' => 'public', 'status' => 'published', 'published_at' => now()->subDay()]);
    Business::create(['name' => 'Warehousing', 'slug' => 'warehousing', 'summary' => 'Warehouse services']);

    $response = $this->get('/search?q=warehouse')->assertOk()
        ->assertSee('Warehouse development')->assertSee('Chattogram warehouse opens')->assertSee('Warehouse policy')->assertSee('Warehousing')
        ->assertDontSee('Draft warehouse');

    $response->assertSeeInOrder(['Warehouse development', 'Other page']);   // title match before body-only match
});

it('treats short, empty and hostile queries safely', function () {
    $this->get('/search')->assertOk();
    $this->get('/search?q=a')->assertOk()->assertDontSee('No results');
    $this->get('/search?q=%25_%5C')->assertOk()->assertSee('No results');
    $this->get('/search?q='.urlencode('<script>alert(1)</script>'))->assertOk()->assertDontSee('<script>alert(1)</script>', false);
});

it('rate limits search', function () {
    foreach (range(1, 30) as $i) {
        $this->get('/search?q=test')->assertOk();
    }
    $this->get('/search?q=test')->assertStatus(429);
});

it('renders branded error pages', function () {
    $this->get('/definitely-not-here')->assertNotFound()->assertSee('Page not found')->assertSee('Search the site');
});

it('lists live pages in the HTML sitemap', function () {
    livePageWith('About us', 'about');
    Page::create(['title' => 'Hidden draft', 'slug' => 'hidden', 'path' => 'hidden', 'status' => 'draft']);

    $this->get('/sitemap')->assertOk()->assertSee('About us')->assertDontSee('Hidden draft');
});

it('falls back gracefully when the site has no settings or menus', function () {
    livePageWith('About us', 'about');

    $this->get('/about')->assertOk();
    $this->get('/')->assertOk();
});
