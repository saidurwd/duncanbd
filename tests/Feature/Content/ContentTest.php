<?php

use App\Domain\Business\Models\Business;
use App\Domain\Business\Models\Estate;
use App\Domain\Cms\Models\Page;
use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use App\Domain\People\Models\Person;
use App\Domain\People\Models\TimelineEvent;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->editor = User::factory()->create([
        'two_factor_secret' => encrypt('S'), 'two_factor_recovery_codes' => encrypt('[]'), 'two_factor_confirmed_at' => now(),
    ])->assignRole(Role::Editor->value);
});

it('creates a business with parsed metrics, a slug and sanitised description', function () {
    $this->actingAs($this->editor)->post(route('admin.content.store', 'businesses'), [
        'name' => 'Tea', 'summary' => 'CTC teas', 'body' => '<p>Hi</p><script>x()</script>',
        'metrics' => "Factories | 14\nHectares | 9,000\n | ignored", 'is_active' => 1,
    ])->assertRedirect(route('admin.content.index', 'businesses'));

    $b = Business::firstOrFail();
    expect($b->slug)->toBe('tea')
        ->and($b->body)->not->toContain('<script')
        ->and($b->metrics)->toBe([['label' => 'Factories', 'value' => '14'], ['label' => 'Hectares', 'value' => '9,000']]);
});

it('creates estates with certifications and numeric coordinates', function () {
    $tea = Business::create(['name' => 'Tea', 'slug' => 'tea']);

    $this->actingAs($this->editor)->post(route('admin.content.store', 'estates'), [
        'business_id' => $tea->id, 'name' => 'Kodala', 'district' => 'Sylhet', 'area_ha' => '812.5',
        'certifications' => "Rainforest Alliance\n\nISO 22000", 'lat' => '24.9', 'lng' => '91.8', 'is_active' => 1,
    ])->assertSessionHasNoErrors();

    $e = Estate::firstOrFail();
    expect($e->certifications)->toBe(['Rainforest Alliance', 'ISO 22000'])->and((float) $e->area_ha)->toBe(812.5);
});

it('validates content input', function () {
    $this->actingAs($this->editor)->post(route('admin.content.store', 'estates'), ['name' => '', 'business_id' => 999, 'lat' => 120, 'lng' => 'x'])
        ->assertSessionHasErrors(['name', 'business_id', 'lat', 'lng']);
    $this->actingAs($this->editor)->post(route('admin.content.store', 'businesses'), ['name' => 'X', 'website_url' => 'http://insecure'])
        ->assertSessionHasErrors('website_url');
});

it('rejects unknown resources and roles without publish rights', function () {
    $this->actingAs($this->editor)->get('/'.config('admin.path').'/content/secrets')->assertNotFound();

    $author = User::factory()->create(['two_factor_secret' => encrypt('S'), 'two_factor_recovery_codes' => encrypt('[]'), 'two_factor_confirmed_at' => now()])->assignRole(Role::Author->value);
    $this->actingAs($author)->get(route('admin.content.index', 'people'))->assertForbidden();
});

it('edits and deletes records', function () {
    $p = Person::create(['name' => 'A Person', 'slug' => 'a-person', 'group' => 'board']);

    $this->actingAs($this->editor)->get(route('admin.content.edit', ['people', $p->id]))->assertOk()->assertSee('A Person');
    $this->actingAs($this->editor)->put(route('admin.content.update', ['people', $p->id]), ['name' => 'B Person', 'group' => 'leadership', 'is_active' => 1])->assertSessionHasNoErrors();
    expect($p->fresh()->name)->toBe('B Person')->and($p->fresh()->slug)->toBe('a-person');

    $this->actingAs($this->editor)->delete(route('admin.content.destroy', ['people', $p->id]));
    expect(Person::count())->toBe(0);
});

it('serves business and estate pages and hides inactive ones', function () {
    $tea = Business::create(['name' => 'Tea', 'slug' => 'tea', 'summary' => 'CTC teas', 'metrics' => [['label' => 'Factories', 'value' => '14']]]);
    Estate::create(['business_id' => $tea->id, 'name' => 'Kodala', 'slug' => 'kodala', 'district' => 'Sylhet', 'area_ha' => 812.5, 'certifications' => ['ISO 22000']]);
    Estate::create(['business_id' => $tea->id, 'name' => 'Hidden Estate', 'slug' => 'hidden', 'is_active' => false]);
    Business::create(['name' => 'Secret', 'slug' => 'secret', 'is_active' => false]);

    $this->get('/what-we-do/tea')->assertOk()->assertSee('Kodala')->assertDontSee('Hidden Estate')->assertSee('Factories');
    $this->get('/what-we-do/tea/estates/kodala')->assertOk()->assertSee('812.5 ha')->assertSee('ISO 22000');
    $this->get('/what-we-do/tea/estates/hidden')->assertNotFound();
    $this->get('/what-we-do/secret')->assertNotFound();
});

it('renders people, timeline and company blocks from records', function () {
    Person::create(['name' => 'Jane Doe', 'slug' => 'jane', 'position' => 'Chair', 'group' => 'board']);
    Person::create(['name' => 'Ex Leader', 'slug' => 'ex', 'group' => 'leadership', 'is_active' => false]);
    TimelineEvent::create(['year' => '1859', 'title' => 'Founded']);
    Business::create(['name' => 'Rubber', 'slug' => 'rubber', 'summary' => 'Two plants']);

    Page::create([
        'title' => 'Who we are', 'slug' => 'who', 'path' => 'who', 'status' => 'published', 'published_at' => now()->subMinute(),
        'blocks' => [
            ['type' => 'people_grid', 'schema_version' => 1, 'data' => ['heading' => 'Board', 'group' => 'board']],
            ['type' => 'timeline', 'schema_version' => 1, 'data' => ['items' => []]],
            ['type' => 'company_accordion', 'schema_version' => 1, 'data' => ['items' => []]],
        ],
    ]);

    $this->get('/who')->assertOk()->assertSee('Jane Doe')->assertDontSee('Ex Leader')->assertSee('1859')->assertSee('Rubber');
});
