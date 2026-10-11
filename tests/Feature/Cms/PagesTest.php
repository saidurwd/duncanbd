<?php

use App\Domain\Cms\Enums\PageStatus;
use App\Domain\Cms\Models\Page;
use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function cmsUser(Role $role): User
{
    return User::factory()->create([
        'two_factor_secret' => encrypt('S'), 'two_factor_recovery_codes' => encrypt('[]'), 'two_factor_confirmed_at' => now(),
    ])->assignRole($role->value);
}

function pagePayload(array $overrides = []): array
{
    return array_replace_recursive([
        'title' => 'About Duncan Brothers',
        'blocks_json' => json_encode([
            ['type' => 'rich_text', 'data' => ['heading' => 'Who we are', 'body' => '<p>Hello <strong>world</strong></p><script>alert(1)</script>']],
            ['type' => 'cta_band', 'data' => ['heading' => 'Join us', 'button_label' => 'Careers', 'button_url' => 'https://example.com/careers', 'rogue' => 'x']],
        ]),
    ], $overrides);
}

it('creates a draft page with sanitised, schema-limited blocks and a first revision', function () {
    $editor = cmsUser(Role::Editor);

    $this->actingAs($editor)->post(route('admin.pages.store'), pagePayload())->assertRedirect();

    $page = Page::firstOrFail();
    expect($page->status)->toBe(PageStatus::Draft)
        ->and($page->path)->toBe('about-duncan-brothers')
        ->and($page->blocks[0]['data']['body'])->not->toContain('<script')
        ->and($page->blocks[0]['data']['body'])->toContain('<strong>world</strong>')
        ->and($page->blocks[1]['data'])->not->toHaveKey('rogue')
        ->and($page->blocks[1]['schema_version'])->toBe(1)
        ->and($page->revisions)->toHaveCount(1);
});

it('validates block data against its schema', function () {
    $this->actingAs(cmsUser(Role::Editor))->post(route('admin.pages.store'), pagePayload([
        'blocks_json' => json_encode([['type' => 'cta_band', 'data' => ['heading' => '', 'button_url' => 'javascript:alert(1)']]]),
    ]))->assertSessionHasErrors(['blocks.0.data.heading', 'blocks.0.data.button_label', 'blocks.0.data.button_url']);

    expect(Page::count())->toBe(0);
});

it('nests child paths under the parent and cascades slug changes', function () {
    $editor = cmsUser(Role::Editor);
    $this->actingAs($editor)->post(route('admin.pages.store'), ['title' => 'Who we are', 'blocks_json' => '[]']);
    $parent = Page::firstOrFail();
    $this->actingAs($editor)->post(route('admin.pages.store'), ['title' => 'History', 'parent_id' => $parent->id, 'blocks_json' => '[]']);

    expect(Page::where('title', 'History')->value('path'))->toBe('who-we-are/history');

    $this->actingAs($editor)->put(route('admin.pages.update', $parent), ['title' => 'About us', 'slug' => 'about-us', 'blocks_json' => '[]']);

    expect(Page::where('title', 'History')->value('path'))->toBe('about-us/history');
});

it('enforces the publishing workflow and permissions', function () {
    $author = cmsUser(Role::Author);
    $editor = cmsUser(Role::Editor);
    $this->actingAs($author)->post(route('admin.pages.store'), pagePayload());
    $page = Page::firstOrFail();

    // Authors may submit for review but not publish.
    $this->actingAs($author)->post(route('admin.pages.transition', $page), ['status' => 'in_review'])->assertSessionHasNoErrors();
    expect($page->fresh()->status)->toBe(PageStatus::InReview);

    $this->actingAs($author)->post(route('admin.pages.transition', $page), ['status' => 'approved'])->assertForbidden();

    // Illegal jump: in review -> published.
    $this->actingAs($editor)->post(route('admin.pages.transition', $page), ['status' => 'published'])->assertSessionHasErrors('status');

    $this->actingAs($editor)->post(route('admin.pages.transition', $page), ['status' => 'approved']);
    $this->actingAs($editor)->post(route('admin.pages.transition', $page), ['status' => 'published']);

    expect($page->fresh()->status)->toBe(PageStatus::Published)->and($page->fresh()->published_at)->not->toBeNull();
});

it('serves only live pages publicly and hides drafts', function () {
    $editor = cmsUser(Role::Editor);
    $this->actingAs($editor)->post(route('admin.pages.store'), pagePayload());
    $page = Page::firstOrFail();

    $this->get('/about-duncan-brothers')->assertNotFound();

    $page->update(['status' => PageStatus::Published, 'published_at' => now()->subMinute()]);
    $this->get('/about-duncan-brothers')->assertOk()->assertSee('Who we are')->assertDontSee('<script>alert(1)', false);
});

it('serves the home page at / and 404s /home', function () {
    Page::create(['title' => 'Home', 'slug' => 'home', 'path' => 'home', 'status' => PageStatus::Published, 'published_at' => now()->subMinute(), 'blocks' => []]);

    $this->get('/')->assertOk();
    $this->get('/home')->assertNotFound();
});

it('publishes scheduled pages and archives expired ones via the scheduler command', function () {
    $due = Page::create(['title' => 'Due', 'slug' => 'due', 'path' => 'due', 'status' => PageStatus::Scheduled, 'published_at' => now()->subMinute()]);
    $later = Page::create(['title' => 'Later', 'slug' => 'later', 'path' => 'later', 'status' => PageStatus::Scheduled, 'published_at' => now()->addDay()]);
    $expired = Page::create(['title' => 'Old', 'slug' => 'old', 'path' => 'old', 'status' => PageStatus::Published, 'published_at' => now()->subDay(), 'unpublish_at' => now()->subMinute()]);

    $this->artisan('pages:publish-scheduled')->assertSuccessful();

    expect($due->fresh()->status)->toBe(PageStatus::Published)
        ->and($later->fresh()->status)->toBe(PageStatus::Scheduled)
        ->and($expired->fresh()->status)->toBe(PageStatus::Archived);
});

it('refuses to schedule in the past', function () {
    $editor = cmsUser(Role::Editor);
    $page = Page::create(['title' => 'P', 'slug' => 'p', 'path' => 'p', 'status' => PageStatus::Approved]);

    $this->actingAs($editor)->post(route('admin.pages.transition', $page), ['status' => 'scheduled', 'published_at' => now()->subDay()->toDateTimeString()])
        ->assertSessionHasErrors('published_at');
});

it('previews drafts only through a valid signed link', function () {
    $page = Page::create(['title' => 'Secret draft', 'slug' => 'secret', 'path' => 'secret', 'status' => PageStatus::Draft, 'blocks' => []]);

    $this->get(route('preview.page', $page))->assertForbidden();
    $this->get(URL::temporarySignedRoute('preview.page', now()->addMinutes(5), ['page' => $page->id]))->assertOk()->assertSee('Preview');
    $this->get(URL::temporarySignedRoute('preview.page', now()->subMinute(), ['page' => $page->id]))->assertForbidden();
});

it('lists revisions and restores an earlier one as a new revision', function () {
    $editor = cmsUser(Role::Editor);
    $this->actingAs($editor)->post(route('admin.pages.store'), pagePayload());
    $page = Page::firstOrFail();
    $first = $page->revisions()->first();

    $this->actingAs($editor)->put(route('admin.pages.update', $page), ['title' => 'Changed title', 'blocks_json' => '[]']);
    expect($page->fresh()->title)->toBe('Changed title');

    $this->actingAs($editor)->get(route('admin.pages.revisions', $page))->assertOk();
    $this->actingAs($editor)->post(route('admin.pages.restore', [$page, $first]))->assertRedirect();

    expect($page->fresh()->title)->toBe('About Duncan Brothers')->and($page->revisions()->count())->toBe(3);
});

it('does not let roles without page permissions in', function () {
    $this->actingAs(cmsUser(Role::Viewer))->get(route('admin.pages.index'))->assertForbidden();
    $this->actingAs(cmsUser(Role::Hr))->post(route('admin.pages.store'), pagePayload())->assertForbidden();
});

it('prevents moving a page beneath its own descendant', function () {
    $editor = cmsUser(Role::Editor);
    $this->actingAs($editor)->post(route('admin.pages.store'), ['title' => 'A', 'blocks_json' => '[]']);
    $a = Page::firstOrFail();
    $this->actingAs($editor)->post(route('admin.pages.store'), ['title' => 'B', 'parent_id' => $a->id, 'blocks_json' => '[]']);
    $b = Page::where('title', 'B')->firstOrFail();

    $this->actingAs($editor)->put(route('admin.pages.update', $a), ['title' => 'A', 'parent_id' => $b->id, 'blocks_json' => '[]'])->assertStatus(422);
});

it('renders the editor forms and listing', function () {
    $editor = cmsUser(Role::Editor);
    $page = Page::create(['title' => "Owner's page", 'slug' => 'owners', 'path' => 'owners', 'status' => PageStatus::Draft, 'blocks' => []]);

    $this->actingAs($editor)->get(route('admin.pages.index'))->assertOk()->assertSee('Owner&#039;s page', false);
    $this->actingAs($editor)->get(route('admin.pages.create'))->assertOk()->assertSee('data-block-editor', false);
    $this->actingAs($editor)->get(route('admin.pages.edit', $page))->assertOk()->assertSee('In review');
});

it('accepts site-relative and https links in blocks but rejects script-capable ones', function () {
    $editor = cmsUser(Role::Editor);
    $cta = fn (string $url) => ['title' => 'T', 'blocks_json' => json_encode([['type' => 'cta_band', 'data' => ['heading' => 'H', 'button_label' => 'B', 'button_url' => $url]]])];

    foreach (['/investors', 'https://example.com/x', 'http://example.com'] as $ok) {
        $this->actingAs($editor)->post(route('admin.pages.store'), $cta($ok))->assertSessionHasNoErrors();
    }
    foreach (['javascript:alert(1)', '//evil.example', 'data:text/html,x', 'ftp://x.example', '/with space'] as $bad) {
        $this->actingAs($editor)->post(route('admin.pages.store'), $cta($bad))->assertSessionHasErrors('blocks.0.data.button_url');
    }
});
