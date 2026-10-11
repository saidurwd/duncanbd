<?php

use App\Domain\Cms\Enums\MenuLocation;
use App\Domain\Cms\MenuRepository;
use App\Domain\Cms\Models\Page;
use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use Database\Seeders\DocumentCategorySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\StarterContentSeeder;

it('seeds the sitemap structure as unpublished drafts and is idempotent', function () {
    $this->seed([RolesAndPermissionsSeeder::class, DocumentCategorySeeder::class]);
    User::factory()->create()->assignRole(Role::SuperAdmin->value);

    $this->seed(StarterContentSeeder::class);
    $this->seed(StarterContentSeeder::class);

    expect(Page::count())->toBe(8)
        ->and(Page::where('status', '!=', 'draft')->count())->toBe(0)
        ->and(Page::where('path', 'who-we-are/history')->value('parent_id'))->toBe(Page::where('path', 'who-we-are')->value('id'))
        ->and(app(MenuRepository::class)->tree(MenuLocation::Explore))->toHaveCount(5);

    $this->get('/')->assertOk()->assertSee('coming soon', false);   // nothing is public until a human publishes
    $this->get('/investors')->assertNotFound();
});
