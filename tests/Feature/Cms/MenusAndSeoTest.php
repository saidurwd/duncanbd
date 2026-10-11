<?php

use App\Domain\Cms\Enums\MenuLocation;
use App\Domain\Cms\Enums\PageStatus;
use App\Domain\Cms\Events\PageContentChanged;
use App\Domain\Cms\MenuRepository;
use App\Domain\Cms\Models\NotFoundEntry;
use App\Domain\Cms\Models\Page;
use App\Domain\Cms\Models\Redirect;
use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Spatie\ResponseCache\Facades\ResponseCache;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function seoUser(Role $role): User
{
    return User::factory()->create([
        'two_factor_secret' => encrypt('S'), 'two_factor_recovery_codes' => encrypt('[]'), 'two_factor_confirmed_at' => now(),
    ])->assignRole($role->value);
}

function livePage(string $title, string $path): Page
{
    return Page::create(['title' => $title, 'slug' => basename($path), 'path' => $path, 'status' => PageStatus::Published, 'published_at' => now()->subMinute(), 'blocks' => []]);
}

// ---- Menus -----------------------------------------------------------------

it('saves a two-level menu and exposes it as a tree', function () {
    $about = livePage('About', 'about');

    $this->actingAs(seoUser(Role::SiteAdmin))->put(route('admin.menus.update', 'explore'), ['items' => [
        ['label' => 'Who we are', 'page_id' => $about->id, 'is_active' => 1],
        ['label' => 'History', 'url' => '/history', 'parent' => 1, 'is_active' => 1],
        ['label' => 'Camellia', 'url' => 'https://camellia.plc.uk', 'target' => '_blank', 'is_active' => 1],
        ['label' => 'Hidden', 'url' => '/hidden', 'is_active' => 0],
    ]])->assertRedirect()->assertSessionHasNoErrors();

    $tree = app(MenuRepository::class)->tree(MenuLocation::Explore);

    expect($tree)->toHaveCount(2)
        ->and($tree[0]['url'])->toBe('/about')
        ->and($tree[0]['children'][0]['label'])->toBe('History')
        ->and($tree[1]['target'])->toBe('_blank');
});

it('rejects javascript: menu URLs and non-managers', function () {
    $this->actingAs(seoUser(Role::SiteAdmin))->put(route('admin.menus.update', 'footer'), ['items' => [
        ['label' => 'Bad', 'url' => 'javascript:alert(1)'],
    ]])->assertSessionHasErrors('items.0.url');

    $this->actingAs(seoUser(Role::Editor))->get(route('admin.menus.index'))->assertForbidden();
});

it('renders the menu editor', function () {
    $this->actingAs(seoUser(Role::SiteAdmin))->get(route('admin.menus.edit', 'explore'))->assertOk()->assertSee('Add item');
});

// ---- Redirects & 404 -------------------------------------------------------

it('redirects legacy URLs case-insensitively, ignoring trailing slash and query', function () {
    Redirect::create(['from_path' => '/about-us.aspx', 'to_url' => '/about', 'status_code' => 301]);

    $this->get('/About-Us.aspx/?utm_source=x')->assertStatus(301)->assertRedirect('/about');
    expect(Redirect::first()->hits)->toBe(1);
});

it('logs unknown URLs once and counts repeat hits', function () {
    $this->get('/nothing-here')->assertNotFound();
    $this->get('/nothing-here')->assertNotFound();

    $entry = NotFoundEntry::firstOrFail();
    expect($entry->path)->toBe('/nothing-here')->and($entry->hits)->toBe(2);
});

it('does not log or redirect admin 404s', function () {
    $this->get('/'.config('admin.path').'/missing')->assertNotFound();

    expect(NotFoundEntry::count())->toBe(0);
});

it('auto-creates a 301 when a published page slug changes, including children', function () {
    $editor = seoUser(Role::Editor);
    $parent = livePage('About', 'about');
    $child = livePage('History', 'about/history');
    $child->update(['parent_id' => $parent->id, 'slug' => 'history']);

    $this->actingAs($editor)->put(route('admin.pages.update', $parent), ['title' => 'About us', 'slug' => 'about-us', 'blocks_json' => '[]']);

    $this->get('/about')->assertStatus(301)->assertRedirect('/about-us');
    $this->get('/about/history')->assertStatus(301)->assertRedirect('/about-us/history');
});

it('collapses redirect chains when a page moves twice', function () {
    $editor = seoUser(Role::Editor);
    $page = livePage('A', 'a');

    foreach (['b', 'c'] as $slug) {
        $this->actingAs($editor)->put(route('admin.pages.update', $page), ['title' => 'A', 'slug' => $slug, 'blocks_json' => '[]']);
    }

    expect(Redirect::where('from_path', '/a')->value('to_url'))->toBe('/c')
        ->and(Redirect::where('from_path', '/b')->value('to_url'))->toBe('/c');
});

it('removes a loop-causing redirect when a page moves back', function () {
    $editor = seoUser(Role::Editor);
    $page = livePage('A', 'a');

    foreach (['b', 'a'] as $slug) {
        $this->actingAs($editor)->put(route('admin.pages.update', $page), ['title' => 'A', 'slug' => $slug, 'blocks_json' => '[]']);
    }

    expect(Redirect::where('from_path', '/a')->exists())->toBeFalse()
        ->and(Redirect::where('from_path', '/b')->value('to_url'))->toBe('/a');
});

it('validates manual redirects', function () {
    $admin = seoUser(Role::SiteAdmin);
    Redirect::create(['from_path' => '/b', 'to_url' => '/a']);

    $this->actingAs($admin)->post(route('admin.redirects.store'), ['from_path' => '/a', 'to_url' => '/b', 'status_code' => 301])
        ->assertSessionHasErrors('to_url');
    $this->actingAs($admin)->post(route('admin.redirects.store'), ['from_path' => '/x', 'to_url' => 'javascript:alert(1)', 'status_code' => 301])
        ->assertSessionHasErrors('to_url');
    $this->actingAs($admin)->post(route('admin.redirects.store'), ['from_path' => '/x', 'to_url' => '/x', 'status_code' => 301])
        ->assertSessionHasErrors('to_url');
    $this->actingAs($admin)->post(route('admin.redirects.store'), ['from_path' => '/'.config('admin.path').'/x', 'to_url' => '/y', 'status_code' => 301])
        ->assertSessionHasErrors('from_path');
});

it('creates a manual redirect and clears its 404 log entry', function () {
    $this->get('/old-thing');
    expect(NotFoundEntry::count())->toBe(1);

    $this->actingAs(seoUser(Role::SiteAdmin))->post(route('admin.redirects.store'), ['from_path' => '/Old-Thing/', 'to_url' => '/new-thing', 'status_code' => 301])
        ->assertRedirect(route('admin.redirects.index'));

    expect(NotFoundEntry::count())->toBe(0);
    $this->get('/old-thing')->assertRedirect('/new-thing');
    $this->actingAs(seoUser(Role::SiteAdmin))->get(route('admin.redirects.index'))->assertOk()->assertSee('/old-thing');
});

it('keeps SEO tools away from editors', function () {
    $this->actingAs(seoUser(Role::Editor))->get(route('admin.redirects.index'))->assertForbidden();
});

// ---- Sitemap & robots ------------------------------------------------------

it('lists only live pages in the sitemap', function () {
    livePage('About', 'about');
    Page::create(['title' => 'Draft', 'slug' => 'draft', 'path' => 'draft', 'status' => PageStatus::Draft]);

    $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml')
        ->assertSee('/about', false)->assertDontSee('/draft', false);
});

it('serves a robots.txt that hides admin and previews and links the sitemap', function () {
    $this->get('/robots.txt')->assertOk()
        ->assertSee('Disallow: /'.config('admin.path').'/', false)
        ->assertSee('Disallow: /preview/', false)
        ->assertSee('Sitemap: ', false);
});

// ---- Guest response cache --------------------------------------------------

it('caches guest pages without cookies and clears the cache when content changes', function () {
    config(['responsecache.enabled' => true, 'responsecache.store' => 'array']);

    $page = livePage('About', 'about');
    $page->update(['blocks' => [['type' => 'rich_text', 'schema_version' => 1, 'data' => ['heading' => 'Original heading', 'body' => '<p>x</p>']]]]);

    $first = $this->get('/about')->assertOk()->assertSee('Original heading');
    expect($first->headers->getCookies())->toBe([]);

    // Change the row behind the cache's back: a cached response still shows the old content.
    DB::table('pages')->where('id', $page->id)->update(['blocks' => json_encode([['type' => 'rich_text', 'schema_version' => 1, 'data' => ['heading' => 'Changed heading', 'body' => '<p>x</p>']]])]);
    $this->get('/about')->assertSee('Original heading')->assertDontSee('Changed heading');

    // A real content change (event) invalidates it.
    event(new PageContentChanged($page));
    $this->get('/about')->assertSee('Changed heading');
});

it('never caches responses for signed-in admins', function () {
    config(['responsecache.enabled' => true, 'responsecache.store' => 'array']);
    livePage('About', 'about');

    $this->actingAs(seoUser(Role::Editor))->get('/about')->assertOk();
    expect(ResponseCache::hasBeenCached(request()->create('/about')))->toBeFalse();
});
