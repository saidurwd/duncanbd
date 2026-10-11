<?php

use App\Domain\Cms\Models\Page;
use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use App\Domain\News\Models\Post;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function newsUser(Role $role): User
{
    return User::factory()->create([
        'two_factor_secret' => encrypt('S'), 'two_factor_recovery_codes' => encrypt('[]'), 'two_factor_confirmed_at' => now(),
    ])->assignRole($role->value);
}

function postData(array $o = []): array
{
    return array_merge(['type' => 'news', 'title' => 'Chattogram warehouse opens', 'body' => '<p>Big <em>news</em></p><script>x()</script>', 'tags' => 'warehouse, Chattogram, warehouse'], $o);
}

it('lets an author draft a post with sanitised body and de-duplicated tags', function () {
    $this->actingAs(newsUser(Role::Author))->post(route('admin.posts.store'), postData())->assertRedirect()->assertSessionHasNoErrors();

    $post = Post::firstOrFail();
    expect($post->status->value)->toBe('draft')
        ->and($post->slug)->toBe('chattogram-warehouse-opens')
        ->and($post->body)->not->toContain('<script')
        ->and($post->tags)->toBe(['warehouse', 'Chattogram']);
});

it('stops authors publishing but lets editors publish and schedule', function () {
    $author = newsUser(Role::Author);
    $this->actingAs($author)->post(route('admin.posts.store'), postData());
    $post = Post::firstOrFail();

    $this->actingAs($author)->post(route('admin.posts.publish', $post))->assertForbidden();

    $editor = newsUser(Role::Editor);
    $this->actingAs($editor)->post(route('admin.posts.publish', $post), ['published_at' => now('Asia/Dhaka')->addDay()->format('Y-m-d\TH:i')])->assertRedirect();
    expect($post->fresh()->published_at->isFuture())->toBeTrue();

    auth()->logout();
    $this->get('/news')->assertDontSee('Chattogram warehouse opens');   // scheduled, not live yet

    $post->update(['published_at' => now()->subMinute()]);
    $this->get('/news')->assertOk()->assertSee('Chattogram warehouse opens');
    $this->get($post->fresh()->url())->assertOk()->assertSee('Big');
});

it('only serves a post at its canonical year URL', function () {
    $post = Post::create(['type' => 'news', 'title' => 'T', 'slug' => 't', 'status' => 'published', 'published_at' => now()->subDay(), 'body' => '<p>x</p>']);

    $this->get('/news/'.now()->year.'/t')->assertOk();
    $this->get('/news/1999/t')->assertNotFound();
});

it('filters by type and hides drafts', function () {
    Post::create(['type' => 'press', 'title' => 'Press one', 'slug' => 'p1', 'status' => 'published', 'published_at' => now()->subDay()]);
    Post::create(['type' => 'news', 'title' => 'News one', 'slug' => 'n1', 'status' => 'published', 'published_at' => now()->subDay()]);
    Post::create(['type' => 'news', 'title' => 'Draft one', 'slug' => 'd1', 'status' => 'draft']);

    $this->get('/news?type=press')->assertSee('Press one')->assertDontSee('News one');
    $this->get('/news')->assertDontSee('Draft one');
});

it('freezes the slug after publication and keeps slugs unique', function () {
    $editor = newsUser(Role::Editor);
    $this->actingAs($editor)->post(route('admin.posts.store'), postData());
    $this->actingAs($editor)->post(route('admin.posts.store'), postData());
    expect(Post::orderBy('id')->pluck('slug')->all())->toBe(['chattogram-warehouse-opens', 'chattogram-warehouse-opens-2']);

    $post = Post::firstOrFail();
    $this->actingAs($editor)->post(route('admin.posts.publish', $post));
    $this->actingAs($editor)->put(route('admin.posts.update', $post), postData(['title' => 'Renamed', 'slug' => 'renamed']));
    expect($post->fresh()->slug)->toBe('chattogram-warehouse-opens');
});

it('keeps non-news roles out of the news admin', function () {
    $this->actingAs(newsUser(Role::Hr))->get(route('admin.posts.index'))->assertForbidden();
    $this->actingAs(newsUser(Role::Viewer))->post(route('admin.posts.store'), postData())->assertForbidden();
});

it('renders the news feed block on a page', function () {
    Post::create(['type' => 'news', 'title' => 'Feed story', 'slug' => 'fs', 'status' => 'published', 'published_at' => now()->subDay()]);
    Page::create([
        'title' => 'Home', 'slug' => 'home', 'path' => 'home', 'status' => 'published', 'published_at' => now()->subMinute(),
        'blocks' => [['type' => 'news_feed', 'schema_version' => 1, 'data' => ['heading' => 'Latest news', 'limit' => 3]]],
    ]);

    $this->get('/')->assertOk()->assertSee('Latest news')->assertSee('Feed story');
});
