<?php

use App\Domain\Cms\Models\Page;
use App\Domain\Documents\Enums\DocumentStatus;
use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Models\DocumentCategory;
use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Models\User;
use Database\Seeders\DocumentCategorySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed([RolesAndPermissionsSeeder::class, DocumentCategorySeeder::class]);
    Storage::fake('local');
    $this->investor = DocumentCategory::where('slug', 'annual-reports')->first();
    $this->general = DocumentCategory::where('slug', 'policies')->first();
});

function docUser(Role $role): User
{
    return User::factory()->create([
        'two_factor_secret' => encrypt('S'), 'two_factor_recovery_codes' => encrypt('[]'), 'two_factor_confirmed_at' => now(),
    ])->assignRole($role->value);
}

function pdf(string $name = 'report.pdf', string $content = "%PDF-1.4\n%test\n"): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, $content);
}

function uploadDoc($test, User $user, array $overrides = [])
{
    return $test->actingAs($user)->post(route('admin.documents.store'), array_merge([
        'title' => 'Annual Report 2025',
        'category_id' => test()->investor->id,
        'visibility' => 'public',
        'financial_year' => '2025',
        'file' => pdf(),
    ], $overrides));
}

it('uploads a document to the private disk with checksum and a randomised path', function () {
    uploadDoc($this, docUser(Role::InvestorRelations))->assertRedirect()->assertSessionHasNoErrors();

    $doc = Document::with('currentVersion')->firstOrFail();
    $v = $doc->currentVersion;

    expect($doc->status)->toBe(DocumentStatus::Draft)
        ->and($doc->slug)->toBe('annual-report-2025')
        ->and($v->version)->toBe(1)
        ->and($v->original_name)->toBe('report.pdf')
        ->and($v->path)->not->toContain('report')
        ->and($v->checksum)->toBe(hash('sha256', "%PDF-1.4\n%test\n"));
    Storage::disk('local')->assertExists($v->path);
});

it('rejects disallowed file types', function () {
    $ir = docUser(Role::InvestorRelations);

    uploadDoc($this, $ir, ['file' => UploadedFile::fake()->create('x.php', 1, 'application/x-php')])->assertSessionHasErrors('file');
    uploadDoc($this, $ir, ['file' => UploadedFile::fake()->create('x.exe', 1)])->assertSessionHasErrors('file');
    uploadDoc($this, $ir, ['file' => UploadedFile::fake()->create('big.pdf', 60000, 'application/pdf')])->assertSessionHasErrors('file');

    expect(Document::count())->toBe(0);
});

it('adds new versions and keeps the old files', function () {
    $ir = docUser(Role::InvestorRelations);
    uploadDoc($this, $ir);
    $doc = Document::firstOrFail();

    $this->actingAs($ir)->put(route('admin.documents.update', $doc), [
        'title' => 'Annual Report 2025', 'category_id' => $this->investor->id, 'visibility' => 'public',
        'file' => pdf('v2.pdf', "%PDF-1.4\n%second\n"),
    ])->assertSessionHasNoErrors();

    $versions = $doc->versions()->get();
    expect($versions)->toHaveCount(2)
        ->and($versions->where('is_current', true))->toHaveCount(1)
        ->and($doc->currentVersion()->first()->version)->toBe(2);
    $versions->each(fn ($v) => Storage::disk('local')->assertExists($v->path));
});

it('enforces investor vs general category permissions', function () {
    $editor = docUser(Role::Editor);
    $hr = docUser(Role::Hr);

    uploadDoc($this, $editor)->assertForbidden();                                              // investor category
    uploadDoc($this, $editor, ['category_id' => $this->general->id])->assertRedirect();        // general category ok
    $this->actingAs($hr)->get(route('admin.documents.index'))->assertForbidden();

    $doc = Document::firstOrFail();
    $investorDoc = Document::create(['category_id' => $this->investor->id, 'title' => 'Secret', 'slug' => 'secret', 'visibility' => 'public']);

    $this->actingAs($editor)->get(route('admin.documents.edit', $investorDoc))->assertForbidden();
    $this->actingAs($editor)->delete(route('admin.documents.destroy', $investorDoc))->assertForbidden();
    // Cannot move a general doc into an investor category.
    $this->actingAs($editor)->put(route('admin.documents.update', $doc), [
        'title' => 'x', 'category_id' => $this->investor->id, 'visibility' => 'public',
    ])->assertForbidden();
});

it('hides investor documents from non-investor users in the list', function () {
    Document::create(['category_id' => $this->investor->id, 'title' => 'Investor only title', 'slug' => 'a', 'visibility' => 'public']);
    Document::create(['category_id' => $this->general->id, 'title' => 'General title', 'slug' => 'b', 'visibility' => 'public']);

    $this->actingAs(docUser(Role::Editor))->get(route('admin.documents.index'))
        ->assertOk()->assertSee('General title')->assertDontSee('Investor only title');
});

it('refuses to publish a document with no file', function () {
    $doc = Document::create(['category_id' => $this->general->id, 'title' => 'Empty', 'slug' => 'empty', 'visibility' => 'public']);

    $this->actingAs(docUser(Role::Editor))->post(route('admin.documents.publish', $doc))->assertStatus(422);
});

it('serves published public documents and counts downloads', function () {
    $ir = docUser(Role::InvestorRelations);
    uploadDoc($this, $ir);
    $doc = Document::firstOrFail();

    $this->get(route('documents.download', $doc->uuid))->assertNotFound();           // draft
    $this->get('/documents/annual-reports/annual-report-2025')->assertNotFound();

    $this->actingAs($ir)->post(route('admin.documents.publish', $doc))->assertRedirect();
    auth()->logout();

    $this->get('/documents')->assertOk()->assertSee('Annual Report 2025');
    $this->get('/documents/annual-reports')->assertOk()->assertSee('Annual Report 2025');
    $this->get('/documents/annual-reports/annual-report-2025')->assertOk();

    $response = $this->get(route('documents.download', $doc->uuid))->assertOk()->assertDownload('report.pdf');
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($doc->fresh()->download_count)->toBe(1);
});

it('never exposes private or expired documents publicly', function () {
    $ir = docUser(Role::InvestorRelations);
    uploadDoc($this, $ir, ['visibility' => 'private']);
    $private = Document::firstOrFail();
    $this->actingAs($ir)->post(route('admin.documents.publish', $private));
    auth()->logout();

    $this->get(route('documents.download', $private->uuid))->assertNotFound();
    $this->get('/documents/annual-reports')->assertDontSee('Annual Report 2025');

    $private->update(['visibility' => 'public', 'expires_at' => now()->subMinute()]);
    $this->get(route('documents.download', $private->uuid))->assertNotFound();
});

it('lets staff download any version through the authenticated route only', function () {
    $ir = docUser(Role::InvestorRelations);
    uploadDoc($this, $ir, ['visibility' => 'private']);
    $doc = Document::firstOrFail();

    $this->actingAs($ir)->get(route('admin.documents.download', [$doc, 1]))->assertOk()->assertDownload('report.pdf');
    $this->actingAs(docUser(Role::Editor))->get(route('admin.documents.download', [$doc, 1]))->assertForbidden();
    auth()->logout();
    $this->get(route('admin.documents.download', [$doc, 1]))->assertRedirect('/login');
});

it('keeps a published document slug stable', function () {
    $ir = docUser(Role::InvestorRelations);
    uploadDoc($this, $ir);
    $doc = Document::firstOrFail();
    $this->actingAs($ir)->post(route('admin.documents.publish', $doc));

    $this->actingAs($ir)->put(route('admin.documents.update', $doc), [
        'title' => 'Renamed report', 'slug' => 'renamed', 'category_id' => $this->investor->id, 'visibility' => 'public',
    ]);

    expect($doc->fresh()->slug)->toBe('annual-report-2025')->and($doc->fresh()->title)->toBe('Renamed report');
});

it('generates unique slugs within a category', function () {
    $ir = docUser(Role::InvestorRelations);
    uploadDoc($this, $ir);
    uploadDoc($this, $ir, ['file' => pdf('b.pdf')]);

    expect(Document::orderBy('id')->pluck('slug')->all())->toBe(['annual-report-2025', 'annual-report-2025-2']);
});

it('manages categories and blocks deleting non-empty ones', function () {
    $ir = docUser(Role::InvestorRelations);
    Document::create(['category_id' => $this->general->id, 'title' => 'D', 'slug' => 'd', 'visibility' => 'public']);

    $this->actingAs($ir)->post(route('admin.documents.categories.store'), ['name' => 'Press kits'])->assertSessionHasNoErrors();
    expect(DocumentCategory::where('slug', 'press-kits')->exists())->toBeTrue();

    $this->actingAs($ir)->delete(route('admin.documents.categories.destroy', $this->general))->assertStatus(422);
    $this->actingAs(docUser(Role::Editor))->get(route('admin.documents.categories.index'))->assertForbidden();
});

it('renders document blocks on a page', function () {
    $ir = docUser(Role::InvestorRelations);
    uploadDoc($this, $ir);
    $doc = Document::firstOrFail();
    $this->actingAs($ir)->post(route('admin.documents.publish', $doc));
    auth()->logout();

    Page::create([
        'title' => 'Investors', 'slug' => 'investors', 'path' => 'investors', 'status' => 'published', 'published_at' => now()->subMinute(),
        'blocks' => [
            ['type' => 'document_list', 'schema_version' => 1, 'data' => ['heading' => 'Latest reports', 'category' => 'annual-reports', 'limit' => 3]],
            ['type' => 'featured_document', 'schema_version' => 1, 'data' => ['heading' => 'Featured', 'document_id' => $doc->id]],
        ],
    ]);

    $this->get('/investors')->assertOk()->assertSee('Latest reports')->assertSee('Annual Report 2025')->assertSee('Open report');
});
