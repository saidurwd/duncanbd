<?php

use App\Domain\Business\Models\Business;
use App\Domain\Business\Models\Estate;
use App\Domain\Careers\Models\Vacancy;
use App\Domain\Cms\Actions\SaveMenu;
use App\Domain\Cms\Enums\MenuLocation;
use App\Domain\Cms\MenuRepository;
use App\Domain\Cms\Models\Page;
use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Models\DocumentCategory;
use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use App\Domain\Media\Models\MediaItem;
use App\Domain\News\Models\Post;
use App\Domain\Settings\SettingsRepository;
use Database\Seeders\DocumentCategorySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SustainabilityPageSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('public');
    config(['media-library.queue_conversions_by_default' => false]);
});

function seoPage(string $path, array $o = []): Page
{
    return Page::create(array_merge(['title' => ucfirst(basename($path)), 'slug' => basename($path), 'path' => $path, 'status' => 'published', 'published_at' => now()->subDay(), 'blocks' => []], $o));
}

function seoAdmin(): User
{
    return User::factory()->create(['two_factor_secret' => encrypt('S'), 'two_factor_recovery_codes' => encrypt('[]'), 'two_factor_confirmed_at' => now()])->assignRole(Role::SiteAdmin->value);
}

it('emits one robots tag, defaulting to index with large image previews', function () {
    seoPage('about');

    $html = $this->get('/about')->getContent();

    expect(substr_count($html, 'name="robots"'))->toBe(1)->and($html)->toContain('<meta name="robots" content="index, follow, max-image-preview:large">');
});

it('marks private and utility pages noindex exactly once', function () {
    $this->get('/search?q=tea')->assertSee('<meta name="robots" content="noindex, follow">', false);
    $this->get('/newsletter/thanks')->assertSee('<meta name="robots" content="noindex, nofollow">', false);

    $page = seoPage('secret', ['status' => 'draft']);
    $html = $this->get(URL::temporarySignedRoute('preview.page', now()->addHour(), ['page' => $page->id]))->getContent();
    expect(substr_count($html, 'name="robots"'))->toBe(1)->and($html)->toContain('noindex, nofollow');
});

it('emits Twitter card and Open Graph tags, using summary when there is no image', function () {
    seoPage('about');

    $this->get('/about')->assertSee('<meta name="twitter:card" content="summary">', false)
        ->assertSee('property="og:locale"', false)->assertSee('name="twitter:title"', false)->assertDontSee('og:image', false);
});

it('uses the site-wide default share image, with its size, when a page has none', function () {
    seoPage('about');
    $this->actingAs(seoAdmin())->post(route('admin.settings.branding'), ['share_image' => UploadedFile::fake()->image('share.jpg', 1200, 630)])->assertSessionHasNoErrors();
    $path = app(SettingsRepository::class)->get('branding', 'share_image')['path'];
    auth()->logout();

    $this->get('/about')->assertSee('<meta property="og:image" content="'.url($path).'">', false)
        ->assertSee('<meta property="og:image:width" content="1200">', false)->assertSee('<meta property="og:image:height" content="630">', false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)->assertSee('<meta name="twitter:image" content="'.url($path).'">', false);
});

it('lets a page choose its own share image over the default', function () {
    $item = MediaItem::create(['title' => 'Hero', 'alt_text' => 'x']);
    $item->addMedia(UploadedFile::fake()->image('own.jpg', 1200, 630))->toMediaCollection('file');
    seoPage('about', ['seo' => ['og_media_id' => $item->id]]);

    $this->get('/about')->assertSee('og:image" content="'.$item->getFirstMediaUrl('file', 'large'), false);
});

it('validates the share image size and type', function () {
    $post = fn ($file) => $this->actingAs(seoAdmin())->post(route('admin.settings.branding'), ['share_image' => $file]);

    $post(UploadedFile::fake()->image('small.png', 300, 200))->assertSessionHasErrors('share_image');
    $post(UploadedFile::fake()->create('share.svg', 5, 'image/svg+xml'))->assertSessionHasErrors('share_image');
    expect(MediaItem::count())->toBe(0);
});

it('adds Search Console and Bing verification tags from settings and validates them', function () {
    seoPage('about');
    $admin = seoAdmin();

    $this->actingAs($admin)->put(route('admin.settings.update'), ['identity' => ['site_name' => 'X'], 'seo' => ['google_site_verification' => 'abc-DEF_123', 'bing_site_verification' => 'ABCDEF0123']])->assertSessionHasNoErrors();
    auth()->logout();
    $this->get('/about')->assertSee('<meta name="google-site-verification" content="abc-DEF_123">', false)->assertSee('<meta name="msvalidate.01" content="ABCDEF0123">', false);

    $this->actingAs($admin)->put(route('admin.settings.update'), ['identity' => ['site_name' => 'X'], 'seo' => ['google_site_verification' => '"><script>alert(1)</script>']])->assertSessionHasErrors('seo.google_site_verification');
});

it('describes the home page as a WebSite with a site search action', function () {
    seoPage('home');

    $this->get('/')->assertSee('"@type":"WebSite"', false)->assertSee('"@type":"SearchAction"', false)->assertSee('search?q={search_term_string}', false);
});

it('marks news as an article with dates, image data and Article structured data', function () {
    $item = MediaItem::create(['title' => 'Pic', 'alt_text' => 'x']);
    $item->addMedia(UploadedFile::fake()->image('story.jpg', 1200, 630))->toMediaCollection('file');
    $post = Post::create(['type' => 'news', 'title' => 'A story', 'slug' => 'a-story', 'excerpt' => 'Short.', 'body' => '<p>x</p>', 'status' => 'published', 'published_at' => now()->subDay(), 'featured_media_id' => $item->id]);

    $html = $this->get($post->url())->assertOk()->getContent();

    expect($html)->toContain('<meta property="og:type" content="article">')->toContain('article:published_time')
        ->toContain('"@type":"NewsArticle"')->toContain('"headline":"A story"')->toContain('og:image" content="'.$item->getFirstMediaUrl('file', 'large'));
});

it('lists everything public in the sitemap and nothing private', function () {
    $this->seed(DocumentCategorySeeder::class);
    seoPage('about');
    seoPage('hidden', ['status' => 'draft']);
    Post::create(['type' => 'news', 'title' => 'Live story', 'slug' => 'live-story', 'status' => 'published', 'published_at' => now()->subDay()]);
    Post::create(['type' => 'news', 'title' => 'Draft story', 'slug' => 'draft-story', 'status' => 'draft']);
    $cat = DocumentCategory::where('slug', 'annual-reports')->first();
    Document::create(['category_id' => $cat->id, 'title' => 'Public report', 'slug' => 'public-report', 'visibility' => 'public', 'status' => 'published', 'published_at' => now()->subDay()]);
    Document::create(['category_id' => $cat->id, 'title' => 'Private report', 'slug' => 'private-report', 'visibility' => 'private', 'status' => 'published', 'published_at' => now()->subDay()]);
    Vacancy::create(['title' => 'Open job', 'slug' => 'open-job', 'employment_type' => 'full_time', 'status' => 'published', 'published_at' => now()->subDay()]);
    Vacancy::create(['title' => 'Closed job', 'slug' => 'closed-job', 'employment_type' => 'full_time', 'status' => 'published', 'published_at' => now()->subDay(), 'closing_date' => now()->subDay()->toDateString()]);
    $tea = Business::create(['name' => 'Tea', 'slug' => 'tea']);
    Estate::create(['business_id' => $tea->id, 'name' => 'Lungla', 'slug' => 'lungla']);
    Estate::create(['business_id' => $tea->id, 'name' => 'Hidden', 'slug' => 'hidden-estate', 'is_active' => false]);

    $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

    foreach (['/about', '/news', '/documents', '/careers/vacancies', '/contact', '/news/'.now()->year.'/live-story', '/documents/annual-reports/public-report', '/careers/vacancies/open-job', '/what-we-do/tea/estates/lungla'] as $path) {
        expect($xml)->toContain('<loc>'.url($path).'</loc>');
    }
    foreach (['/hidden', 'draft-story', 'private-report', 'closed-job', 'hidden-estate'] as $path) {
        expect($xml)->not->toContain($path);
    }
    expect($xml)->toContain('<lastmod>')->toContain('<priority>');
});

it('builds the Sustainability page from sourced content, idempotently, and adds it to the menu', function () {
    User::factory()->create()->assignRole(Role::SuperAdmin->value);
    app(SaveMenu::class)(MenuLocation::Explore, [
        ['label' => 'About Duncan', 'url' => '/a', 'is_active' => true], ['label' => 'Corporate Responsibility', 'url' => '/c', 'is_active' => true], ['label' => 'Gallery', 'url' => '/g', 'is_active' => true],
    ]);

    $this->seed(SustainabilityPageSeeder::class);
    $this->seed(SustainabilityPageSeeder::class);

    $page = Page::where('path', 'sustainability')->firstOrFail();
    $types = array_column($page->blocks, 'type');
    expect(Page::where('path', 'sustainability')->count())->toBe(1)->and($page->status->value)->toBe('published')
        ->and($types)->toContain('hero', 'stats', 'cards_grid', 'certifications')
        ->and(collect($page->blocks)->firstWhere('type', 'certifications')['data']['items'])->toHaveCount(5);

    $labels = array_column(app(MenuRepository::class)->tree(MenuLocation::Explore), 'label');
    expect($labels)->toBe(['About Duncan', 'Corporate Responsibility', 'Sustainability', 'Gallery']);

    $this->get('/sustainability')->assertOk()->assertSee('ISO 22000:2005')->assertSee('RTZ World Development Award')->assertSee('Certifications, standards and awards');
});

it('validates certification blocks and rejects script-capable links', function () {
    $editor = User::factory()->create(['two_factor_secret' => encrypt('S'), 'two_factor_recovery_codes' => encrypt('[]'), 'two_factor_confirmed_at' => now()])->assignRole(Role::Editor->value);
    $blocks = fn (array $item) => ['title' => 'T', 'blocks_json' => json_encode([['type' => 'certifications', 'data' => ['heading' => 'H', 'items' => [$item]]]])];

    $this->actingAs($editor)->post(route('admin.pages.store'), $blocks(['name' => 'ISO 9001', 'issuer' => 'ISO', 'url' => 'https://www.iso.org']))->assertSessionHasNoErrors();
    $this->actingAs($editor)->post(route('admin.pages.store'), $blocks(['name' => '']))->assertSessionHasErrors('blocks.0.data.items.0.name');
    $this->actingAs($editor)->post(route('admin.pages.store'), $blocks(['name' => 'X', 'url' => 'javascript:alert(1)']))->assertSessionHasErrors('blocks.0.data.items.0.url');
});
