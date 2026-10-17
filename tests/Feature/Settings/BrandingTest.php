<?php

use App\Domain\Cms\Models\Page;
use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use App\Domain\Media\Models\MediaItem;
use App\Domain\Settings\SettingsRepository;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('public');
    config(['media-library.queue_conversions_by_default' => false]);
    // The public layout (header/footer) only renders on real pages, not the "coming soon" fallback.
    Page::create(['title' => 'Home', 'slug' => 'home', 'path' => 'home', 'status' => 'published', 'published_at' => now()->subDay(), 'blocks' => []]);
});

function brandingAdmin(Role $role = Role::SiteAdmin): User
{
    return User::factory()->create(['two_factor_secret' => encrypt('S'), 'two_factor_recovery_codes' => encrypt('[]'), 'two_factor_confirmed_at' => now()])->assignRole($role->value);
}

it('uploads a logo and shows it in the site header and footer', function () {
    $this->actingAs(brandingAdmin())->post(route('admin.settings.branding'), ['logo' => UploadedFile::fake()->image('logo.png', 600, 200)])
        ->assertRedirect(route('admin.settings.edit'))->assertSessionHasNoErrors();

    $logo = app(SettingsRepository::class)->get('branding', 'logo');
    expect($logo['path'])->toStartWith('/storage/')->and($logo['width'])->toBe(600)->and($logo['height'])->toBe(200);

    auth()->logout();
    $html = $this->get('/')->getContent();
    expect(substr_count($html, 'src="'.$logo['path'].'"'))->toBe(2)                      // header + footer
        ->and($html)->toContain('class="site-header__logo"')->not->toContain('site-header__name');
});

it('falls back to the text brand when no logo is set', function () {
    $html = $this->get('/')->getContent();

    expect($html)->toContain('site-header__name')->not->toContain('site-header__logo');
});

it('replaces a logo and deletes the previous file', function () {
    $admin = brandingAdmin();
    $this->actingAs($admin)->post(route('admin.settings.branding'), ['logo' => UploadedFile::fake()->image('one.png', 400, 120)]);
    $first = app(SettingsRepository::class)->get('branding', 'logo');
    Storage::disk('public')->assertExists(MediaItem::find($first['id'])->getFirstMedia('file')->getPathRelativeToRoot());

    $this->actingAs($admin)->post(route('admin.settings.branding'), ['logo' => UploadedFile::fake()->image('two.png', 500, 150)]);
    $second = app(SettingsRepository::class)->get('branding', 'logo');

    expect($second['id'])->not->toBe($first['id'])->and($second['width'])->toBe(500)
        ->and(MediaItem::withTrashed()->find($first['id']))->toBeNull()
        ->and(MediaItem::count())->toBe(1);
});

it('removes a logo', function () {
    $admin = brandingAdmin();
    $this->actingAs($admin)->post(route('admin.settings.branding'), ['logo' => UploadedFile::fake()->image('one.png', 400, 120)]);

    $this->actingAs($admin)->post(route('admin.settings.branding'), ['remove' => ['logo']])->assertSessionHasNoErrors();

    expect(app(SettingsRepository::class)->get('branding', 'logo'))->toBeNull()->and(MediaItem::withTrashed()->count())->toBe(0);
});

it('sets the favicon and the sign-in logo', function () {
    $this->actingAs(brandingAdmin())->post(route('admin.settings.branding'), [
        'favicon' => UploadedFile::fake()->image('icon.png', 128, 128),
        'logo_dark' => UploadedFile::fake()->image('dark.png', 500, 150),
    ])->assertSessionHasNoErrors();

    $favicon = app(SettingsRepository::class)->get('branding', 'favicon');
    auth()->logout();
    $this->get('/')->assertSee('<link rel="icon" href="'.$favicon['path'].'" type="image/png">', false);
    $this->get('/login')->assertSee('login-logo__img', false);
});

it('rejects SVG, scripts, oversized, tiny and non-square favicon uploads', function () {
    $admin = brandingAdmin();
    $post = fn (array $data) => $this->actingAs($admin)->post(route('admin.settings.branding'), $data);

    $post(['logo' => UploadedFile::fake()->create('logo.svg', 5, 'image/svg+xml')])->assertSessionHasErrors('logo');
    $post(['logo' => UploadedFile::fake()->create('logo.php', 5, 'application/x-php')])->assertSessionHasErrors('logo');
    $post(['logo' => UploadedFile::fake()->image('big.png', 400, 120)->size(3000)])->assertSessionHasErrors('logo');
    $post(['logo' => UploadedFile::fake()->image('tiny.png', 40, 20)])->assertSessionHasErrors('logo');
    $post(['favicon' => UploadedFile::fake()->image('wide.png', 200, 100)])->assertSessionHasErrors('favicon');
    $post(['favicon' => UploadedFile::fake()->image('fav.jpg', 128, 128)])->assertSessionHasErrors('favicon');

    expect(MediaItem::count())->toBe(0);
});

it('allows only users with settings.manage', function () {
    $post = ['logo' => UploadedFile::fake()->image('logo.png', 400, 120)];

    $this->actingAs(brandingAdmin(Role::Editor))->post(route('admin.settings.branding'), $post)->assertForbidden();
    $this->actingAs(brandingAdmin(Role::Viewer))->post(route('admin.settings.branding'), $post)->assertForbidden();
    expect(MediaItem::count())->toBe(0);
});

it('adds the logo to the Organization structured data', function () {
    $this->actingAs(brandingAdmin())->post(route('admin.settings.branding'), ['logo' => UploadedFile::fake()->image('logo.png', 600, 200)]);
    $path = app(SettingsRepository::class)->get('branding', 'logo')['path'];

    auth()->logout();
    $this->get('/')->assertSee('"logo":"'.url($path).'"', false)->assertOk();
});

it('shows the branding section on the settings screen', function () {
    $this->actingAs(brandingAdmin())->get(route('admin.settings.edit'))->assertOk()->assertSee('Branding: logo and favicon')->assertSee('name="logo"', false);
});
