<?php

use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Models\DocumentCategory;
use Database\Seeders\DocumentCategorySeeder;
use Illuminate\Support\Facades\Storage;

it('sends the security headers on every response, including 404s and CSRF failures', function () {
    $headers = fn ($r) => [
        $r->headers->get('X-Content-Type-Options'), $r->headers->get('Cross-Origin-Opener-Policy'),
        $r->headers->get('Permissions-Policy') !== null, str_contains((string) $r->headers->get('Content-Security-Policy'), "frame-ancestors 'self'"),
    ];

    $notFound = $this->get('/definitely-not-a-page');
    expect($notFound->status())->toBe(404)->and($headers($notFound))->toBe(['nosniff', 'same-origin', true, true]);

    $json = $this->getJson('/forms/token');
    expect($headers($json))->toBe(['nosniff', 'same-origin', true, true]);
});

it('never duplicates a security header', function () {
    $response = $this->get('/definitely-not-a-page');

    expect($response->headers->all('X-Frame-Options'))->toHaveCount(1)
        ->and($response->headers->all('Content-Security-Policy'))->toHaveCount(1);
});

it('sets a CSP that blocks inline scripts, eval, framing and third-party hosts by default', function () {
    $csp = (string) $this->get('/login')->headers->get('Content-Security-Policy');

    expect($csp)->toContain("default-src 'self'")->toContain("object-src 'none'")->toContain("frame-ancestors 'self'")->toContain("base-uri 'self'")
        ->not->toContain('unsafe-inline')->not->toContain('unsafe-eval')->not->toContain('googletagmanager')->not->toContain('challenges.cloudflare.com');
});

it('does not set a JavaScript-readable XSRF-TOKEN cookie', function () {
    $names = collect($this->get('/contact')->headers->getCookies())->map->getName();

    expect($names)->not->toContain('XSRF-TOKEN');
});

it('marks the session cookie HttpOnly and SameSite', function () {
    $cookie = collect($this->get('/contact')->headers->getCookies())->first(fn ($c) => str_contains($c->getName(), 'session'));

    expect($cookie)->not->toBeNull()->and($cookie->isHttpOnly())->toBeTrue()->and($cookie->getSameSite())->toBe('lax');
});

it('keeps admin pages out of caches and search engines', function () {
    $response = $this->get('/'.config('admin.path'));

    expect($response->headers->get('X-Robots-Tag'))->toBe('noindex, nofollow')->and($response->headers->get('Cache-Control'))->toContain('no-store');
});

it('returns 404, not a 500, when a published document file is missing on disk', function () {
    $this->seed(DocumentCategorySeeder::class);
    Storage::fake('local');
    $doc = Document::create(['category_id' => DocumentCategory::first()->id, 'title' => 'Ghost', 'slug' => 'ghost', 'visibility' => 'public', 'status' => 'published', 'published_at' => now()->subDay()]);
    $doc->versions()->create(['version' => 1, 'path' => 'documents/missing.pdf', 'original_name' => 'x.pdf', 'mime' => 'application/pdf', 'size' => 1, 'checksum' => str_repeat('a', 64), 'is_current' => true]);

    $this->get(route('documents.download', $doc->uuid))->assertNotFound();
});

it('does not leak stack traces on server errors when debug is off', function () {
    config(['app.debug' => false]);
    Route::get('/_boom', fn () => throw new RuntimeException('secret internals'));

    $this->get('/_boom')->assertStatus(500)->assertDontSee('secret internals')->assertDontSee('RuntimeException');
});

it('does not allow HTTP methods that should not exist', function () {
    $this->call('TRACE', '/')->assertStatus(405);
    $this->call('DELETE', '/contact')->assertStatus(405);
});

it('exposes no sensitive files or debug endpoints', function () {
    foreach (['/.env', '/composer.json', '/storage/logs/laravel.log', '/telescope', '/_ignition/health-check', '/horizon', '/vendor/autoload.php'] as $path) {
        expect($this->get($path)->status())->toBeIn([404, 405], "$path should not be reachable");
    }
});
