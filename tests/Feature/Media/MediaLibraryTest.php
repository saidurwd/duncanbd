<?php

use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use App\Domain\Media\Models\MediaFolder;
use App\Domain\Media\Models\MediaItem;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    Storage::fake('public');
    config(['media-library.queue_conversions_by_default' => false]);
});

function mediaUser(Role $role): User
{
    return User::factory()->create([
        'two_factor_secret' => encrypt('S'),
        'two_factor_recovery_codes' => encrypt('[]'),
        'two_factor_confirmed_at' => now(),
    ])->assignRole($role->value);
}

it('uploads an image with alt text and a randomised stored filename', function () {
    $this->actingAs(mediaUser(Role::Editor))->post(route('admin.media.store'), [
        'files' => [UploadedFile::fake()->image('Estate Photo.jpg', 800, 600)],
        'alt_text' => 'Tea pickers on a Sylhet estate',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $item = MediaItem::first();
    $stored = $item->getFirstMedia('file');

    expect($item->title)->toBe('Estate Photo')
        ->and($stored->file_name)->not->toContain('Estate')
        ->and($item->altAttribute())->toBe('Tea pickers on a Sylhet estate');
});

it('requires alt text unless the image is flagged decorative', function () {
    $user = mediaUser(Role::Editor);

    $this->actingAs($user)->post(route('admin.media.store'), [
        'files' => [UploadedFile::fake()->image('a.jpg')],
    ])->assertSessionHasErrors('alt_text');

    $this->actingAs($user)->post(route('admin.media.store'), [
        'files' => [UploadedFile::fake()->image('a.jpg')],
        'is_decorative' => 1,
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect(MediaItem::first()->altAttribute())->toBe('');
});

it('rejects non-image and svg uploads', function () {
    $user = mediaUser(Role::Editor);

    $this->actingAs($user)->post(route('admin.media.store'), [
        'files' => [UploadedFile::fake()->create('evil.svg', 5, 'image/svg+xml')],
        'alt_text' => 'x',
    ])->assertSessionHasErrors('files.0');

    $this->actingAs($user)->post(route('admin.media.store'), [
        'files' => [UploadedFile::fake()->create('shell.php', 5, 'application/x-php')],
        'alt_text' => 'x',
    ])->assertSessionHasErrors('files.0');

    expect(MediaItem::count())->toBe(0);
});

it('lets viewers browse but not upload or edit', function () {
    $viewer = mediaUser(Role::Viewer);
    $item = MediaItem::create(['title' => 'X', 'alt_text' => 'x']);

    $this->actingAs($viewer)->get(route('admin.media.index'))->assertOk();
    $this->actingAs($viewer)->post(route('admin.media.store'), [])->assertForbidden();
    $this->actingAs($viewer)->get(route('admin.media.edit', $item))->assertForbidden();
    $this->actingAs($viewer)->delete(route('admin.media.destroy', $item))->assertForbidden();
});

it('denies the media area to roles without media permissions', function () {
    $user = User::factory()->create([
        'two_factor_secret' => encrypt('S'), 'two_factor_recovery_codes' => encrypt('[]'), 'two_factor_confirmed_at' => now(),
    ]);

    $this->actingAs($user)->get(route('admin.media.index'))->assertForbidden();
});

it('updates metadata, filters by folder and soft-deletes', function () {
    $user = mediaUser(Role::SiteAdmin);
    $folder = MediaFolder::create(['name' => 'Estates']);
    $item = MediaItem::create(['title' => 'Old', 'alt_text' => 'old']);

    $this->actingAs($user)->put(route('admin.media.update', $item), [
        'title' => 'New', 'alt_text' => 'new alt', 'folder_id' => $folder->id,
    ])->assertRedirect(route('admin.media.index'));

    expect($item->fresh()->folder_id)->toBe($folder->id);

    $this->actingAs($user)->get(route('admin.media.index', ['folder' => $folder->id]))->assertSee('New');

    $this->actingAs($user)->delete(route('admin.media.destroy', $item))->assertRedirect();
    expect(MediaItem::count())->toBe(0)->and(MediaItem::withTrashed()->count())->toBe(1);
});

it('renders pagination with Bootstrap markup the admin styles understand', function () {
    foreach (range(1, 30) as $i) {
        MediaItem::create(['title' => "Item $i", 'alt_text' => 'x']);
    }

    $this->actingAs(mediaUser(Role::SiteAdmin))->get(route('admin.media.index'))
        ->assertOk()->assertSee('class="pagination"', false)->assertSee('page-item', false)->assertSee('page-link', false);
});
