<?php

namespace App\Domain\Cms\Legacy;

use App\Domain\Cms\Actions\SaveMenu;
use App\Domain\Cms\Actions\SavePage;
use App\Domain\Cms\Enums\MenuLocation;
use App\Domain\Cms\Models\Menu;
use App\Domain\Cms\Models\Page;
use App\Domain\Documents\Actions\StoreDocumentVersion;
use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Models\DocumentCategory;
use App\Domain\Identity\Models\User;
use App\Domain\Media\Models\MediaFolder;
use App\Domain\Media\Models\MediaItem;
use App\Domain\Settings\SettingsRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Loads the manifest written by tools/legacy/crawl.py into the CMS.
 * Everything arrives unpublished (pages, documents) and the run is idempotent: existing records are skipped.
 */
class LegacyImporter
{
    /** @var array<string, int> legacy file name => media item id */
    private array $mediaIds = [];

    /** @var array<string, int> */
    public array $stats = ['media' => 0, 'documents' => 0, 'pages' => 0, 'pages_pending_content' => 0, 'settings' => 0, 'skipped' => 0];

    /** @var list<string> */
    public array $pendingContent = [];

    public function __construct(
        private SavePage $savePage,
        private SaveMenu $saveMenu,
        private StoreDocumentVersion $storeVersion,
        private SettingsRepository $settings,
        private LegacyHtml $html,
    ) {}

    /** @param array<string, mixed> $manifest */
    public function import(array $manifest, string $filesDir, User $user): void
    {
        if (! is_dir($filesDir)) {
            throw new RuntimeException("Asset folder not found: {$filesDir}");
        }

        DB::transaction(function () use ($manifest, $filesDir, $user) {
            $this->importMedia($manifest, $filesDir, $user);
            $this->importDocuments($manifest, $filesDir, $user);
            $this->importSettings($manifest);
            $this->importPages($manifest, $user);
            $this->importMenu($manifest);
        });
    }

    // ---- media ------------------------------------------------------------------------------------

    /** @param array<string, mixed> $manifest */
    private function importMedia(array $manifest, string $filesDir, User $user): void
    {
        $inGallery = [];

        foreach ($manifest['gallery'] ?? [] as $album) {
            $folder = MediaFolder::firstOrCreate(['name' => $album['category']]);
            foreach ($album['images'] as $file) {
                $inGallery[$file] = true;
                $this->importImage($filesDir, $file, $folder->id, $user);
            }
        }

        // Remaining images (home banners, logos used on pages) go in a holding folder.
        $others = collect(glob($filesDir.'/*') ?: [])->map(fn ($p) => basename($p))
            ->filter(fn ($f) => preg_match('/\.(jpe?g|png|webp)$/i', $f) && ! isset($inGallery[$f]));
        if ($others->isNotEmpty()) {
            $folder = MediaFolder::firstOrCreate(['name' => 'Legacy site images']);
            foreach ($others as $file) {
                $this->importImage($filesDir, $file, $folder->id, $user);
            }
        }
    }

    private function importImage(string $filesDir, string $file, int $folderId, User $user): void
    {
        if ($existing = Media::where('custom_properties->legacy_file', $file)->first()) {
            $this->mediaIds[$file] = (int) $existing->model_id;
            $this->stats['skipped']++;

            return;
        }

        $path = $filesDir.'/'.$file;
        if (! is_file($path)) {
            return;
        }

        // Legacy filenames are descriptive ("1486437447_tapping_(female_worker).jpg"): reuse them as title and
        // starting alt text. They are flagged in the report for an editor to review.
        $title = Str::of(pathinfo($file, PATHINFO_FILENAME))->replaceMatches('/^\d{8,}_/', '')->replace(['_', '-'], ' ')->squish()->ucfirst()->toString();

        $item = MediaItem::create([
            'folder_id' => $folderId,
            'title' => $title,
            'alt_text' => $title,
            'uploaded_by' => $user->id,
        ]);

        $item->addMedia($path)->preservingOriginal()
            ->usingFileName(Str::random(40).'.'.strtolower(pathinfo($file, PATHINFO_EXTENSION)))
            ->usingName($title)
            ->withCustomProperties(['legacy_file' => $file])
            ->toMediaCollection(MediaItem::COLLECTION);

        $this->mediaIds[$file] = $item->id;
        $this->stats['media']++;
    }

    // ---- documents --------------------------------------------------------------------------------

    /** @param array<string, mixed> $manifest */
    private function importDocuments(array $manifest, string $filesDir, User $user): void
    {
        $descriptions = [];
        foreach ((array) ($manifest['home']['news'] ?? []) as $news) {
            foreach ((array) ($news['files'] ?? []) as $file) {
                $descriptions[(string) $file] = (string) ($news['title'] ?? '');
            }
        }

        foreach ($manifest['documents'] ?? [] as $doc) {
            $path = $filesDir.'/'.$doc['file'];
            $title = Str::of($doc['title'])->replace('_', ' ')->squish()->toString();
            $slug = Str::slug($title);
            $category = $this->documentCategory($title);

            if (! is_file($path) || Document::withTrashed()->where('category_id', $category->id)->where('slug', $slug)->exists()) {
                $this->stats['skipped']++;

                continue;
            }

            $document = Document::create([
                'category_id' => $category->id,
                'title' => $title,
                'slug' => $slug,
                'description' => $descriptions[$doc['file']] ?? null,
                'financial_year' => preg_match('/\b(20\d{2})\b/', $title, $m) ? $m[1] : null,
                'visibility' => 'public',
                'status' => 'draft',
                'created_by' => $user->id,
            ]);

            ($this->storeVersion)($document, new UploadedFile($path, $doc['file'], mime_content_type($path) ?: 'application/pdf', null, true), $user);
            $this->stats['documents']++;
        }
    }

    private function documentCategory(string $title): DocumentCategory
    {
        $slug = match (true) {
            (bool) preg_match('/annual report/i', $title) => 'annual-reports',
            (bool) preg_match('/interim|half/i', $title) => 'interim-reports',
            (bool) preg_match('/agm|egm|notice/i', $title) => 'agm-egm-notices',
            default => 'statutory-disclosures',
        };

        return DocumentCategory::firstOrCreate(['slug' => $slug], ['name' => Str::headline($slug), 'is_investor' => true, 'sort_order' => 99]);
    }

    // ---- settings ---------------------------------------------------------------------------------

    /** @param array<string, mixed> $manifest */
    private function importSettings(array $manifest): void
    {
        $set = function (string $group, string $key, mixed $value) {
            if (filled($value) && blank($this->settings->get($group, $key))) {
                $this->settings->set($group, $key, $value);
                $this->stats['settings']++;
            }
        };

        $set('identity', 'site_name', 'Duncan Brothers (Bangladesh) Limited');
        $set('identity', 'group_name', 'Camellia Plc');
        $set('identity', 'group_url', 'https://www.camellia.plc.uk');

        $lines = collect(preg_split('/\R/', (string) ($manifest['contact']['text'] ?? '')))->map(fn ($l) => trim($l))->filter();
        $address = $lines->skip(1)->takeUntil(fn ($l) => preg_match('/^(tel|telephone|fax|phone|fields)/i', $l))->implode("\n");
        $phone = $lines->first(fn ($l) => preg_match('/^tel/i', $l));

        $set('contact', 'address_dhaka', $address);
        $set('contact', 'phone', $phone ? trim(preg_replace('/^tel(ephone)?:?\s*/i', '', $phone)) : null);
    }

    // ---- pages ------------------------------------------------------------------------------------

    /** @param array<string, mixed> $manifest */
    private function importPages(array $manifest, User $user): void
    {
        $byHref = [];
        foreach ($this->rows($manifest, 'pages') as $row) {
            $byHref[(string) $row['href']] = $row;
        }
        $made = []; // "group|index" => Page, to resolve parents

        foreach ($manifest['nav'] ?? [] as $group) {
            $section = null;

            foreach ($group['items'] as $i => $item) {
                $isGallery = str_starts_with($item['href'], '/gallery/');
                $parent = $i === 0 ? null : ($item['parent'] !== null ? ($made[$group['title'].'|'.$item['parent']] ?? $section) : $section);

                $page = $isGallery
                    ? $this->galleryPage($manifest, $item, $parent, $user)
                    : $this->contentPage(($byHref[$item['href']] ?? null), $item, $parent, $user, $i === 0 ? $group['title'] : null);

                $made[$group['title'].'|'.$i] = $page;
                $section ??= $page;
            }
        }

        // The standalone gallery with every album.
        $blocks = [];
        foreach ($this->rows($manifest, 'gallery') as $album) {
            if (! empty($album['images'])) {
                $blocks[] = $this->galleryBlock($album);
            }
        }
        if ($blocks !== []) {
            $this->makePage('Gallery', 'gallery', null, $blocks, $user, 'Legacy import: /gallery/index.html');
        }

        $this->homePage($manifest, $user);
    }

    /**
     * @param  array<string, mixed>|null  $legacy
     * @param  array<string, mixed>  $item
     */
    private function contentPage(?array $legacy, array $item, ?Page $parent, User $user, ?string $titleOverride = null): Page
    {
        // A group's first item is its landing page; it takes the group's menu label ("Corporate Responsibility").
        $title = $titleOverride ?? $legacy['title'] ?? $item['title'];
        $source = 'Legacy import: '.$item['href'];

        if (($legacy['status'] ?? null) === 'ok') {
            $clean = $this->html->clean($legacy['body_html'] ?? '');
            $blocks = [];

            if ($clean['html'] !== '') {
                $blocks[] = ['type' => 'rich_text', 'data' => ['heading' => null, 'body' => $clean['html']]];
            }

            $ids = collect(array_merge($clean['images'], $legacy['images'] ?? []))->map(fn ($f) => $this->mediaIds[$f] ?? null)->filter()->unique()->values();
            if ($ids->isNotEmpty()) {
                $blocks[] = ['type' => 'gallery', 'data' => ['heading' => null, 'items' => $ids->map(fn ($id) => ['media_id' => $id])->all()]];
            }
        } else {
            // Not readable at crawl time (login required etc.): keep the structure, flag the gap.
            $blocks = [['type' => 'rich_text', 'data' => ['heading' => null, 'body' => '<p>[CONTENT TO MIGRATE from '.e($item['href']).' — this page could not be read by the crawler.]</p>']]];
            $this->stats['pages_pending_content']++;
            $this->pendingContent[] = $title.' ('.$item['href'].')';
        }

        return $this->makePage($title, Str::slug($title), $parent, $blocks, $user, $source);
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @param  array<string, mixed>  $item
     */
    private function galleryPage(array $manifest, array $item, ?Page $parent, User $user): ?Page
    {
        $album = null;
        foreach ($this->rows($manifest, 'gallery') as $candidate) {
            if (($candidate['source'] ?? null) === $item['href']) {
                $album = $candidate;
            }
        }
        if ($album === null || empty($album['images'])) {
            return null;
        }

        return $this->makePage('Gallery', 'gallery', $parent, [$this->galleryBlock($album)], $user, 'Legacy import: '.$item['href']);
    }

    /**
     * @param  array<string, mixed>  $album
     * @return array{type: string, data: array<string, mixed>}
     */
    private function galleryBlock(array $album): array
    {
        return ['type' => 'gallery', 'data' => [
            'heading' => $album['category'],
            'items' => $this->mediaItems((array) $album['images']),
        ]];
    }

    /** @param array<string, mixed> $manifest */
    private function homePage(array $manifest, User $user): void
    {
        $first = [];
        foreach ($this->rows($manifest, 'gallery') as $album) {
            if (! empty($album['images'][0])) {
                $first[] = $album['images'][0];
            }
        }
        $slides = array_slice($this->mediaItems($first), 0, 5);

        $blocks = array_values(array_filter([
            ['type' => 'hero', 'data' => ['heading' => 'Duncan Brothers (Bangladesh) Limited', 'intro' => null, 'slides' => $slides]],
            ! empty($manifest['home']['intro']) ? ['type' => 'rich_text', 'data' => ['heading' => null, 'body' => '<p>'.e($manifest['home']['intro']).'</p>']] : null,
            ['type' => 'document_list', 'data' => ['heading' => 'Latest news & information', 'category' => 'annual-reports', 'limit' => 3]],
        ]));

        $this->makePage('Home', 'home', null, $blocks, $user, 'Legacy import: /index.html');
    }

    /** @param list<array<string, mixed>> $blocks */
    private function makePage(string $title, string $slug, ?Page $parent, array $blocks, User $user, string $note): Page
    {
        $path = ltrim(($parent ? $parent->path.'/' : '').$slug, '/');

        if ($existing = Page::where('path', $path)->first()) {
            $this->stats['skipped']++;

            return $existing;
        }

        $page = ($this->savePage)(null, ['title' => $title, 'slug' => $slug, 'parent_id' => $parent?->id, 'blocks' => $blocks], $user, $note);
        $this->stats['pages']++;

        return $page;
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return list<array<string, mixed>>
     */
    private function rows(array $manifest, string $key): array
    {
        return array_values(array_filter((array) ($manifest[$key] ?? []), 'is_array'));
    }

    /**
     * @param  array<int, mixed>  $files  legacy file names
     * @return list<array{media_id: int}>
     */
    private function mediaItems(array $files): array
    {
        $items = [];
        foreach ($files as $file) {
            if (isset($this->mediaIds[(string) $file])) {
                $items[] = ['media_id' => $this->mediaIds[(string) $file]];
            }
        }

        return $items;
    }

    // ---- menu -------------------------------------------------------------------------------------

    /** @param array<string, mixed> $manifest */
    private function importMenu(array $manifest): void
    {
        if (Menu::where('location', MenuLocation::Explore->value)->exists()) {
            return;
        }

        $items = [];

        foreach ($manifest['nav'] ?? [] as $group) {
            $section = Page::where('path', Str::slug($group['title']))->first();
            if (! $section) {
                continue;
            }

            $items[] = ['label' => $group['title'], 'page_id' => $section->id, 'is_active' => true];
            $parentIndex = count($items);

            foreach (array_slice($group['items'], 1) as $item) {
                // Two menu levels only: deeper pages stay reachable from their parent page.
                if ($item['parent'] !== null || str_starts_with($item['href'], '/gallery/')) {
                    continue;
                }
                if ($page = Page::where('path', Str::slug($group['title']).'/'.Str::slug($item['title']))->first()) {
                    $items[] = ['label' => $item['title'], 'page_id' => $page->id, 'parent' => $parentIndex - 1, 'is_active' => true];
                }
            }
        }

        if ($gallery = Page::where('path', 'gallery')->first()) {
            $items[] = ['label' => 'Gallery', 'page_id' => $gallery->id, 'is_active' => true];
        }
        $items[] = ['label' => 'Contact us', 'url' => '/contact', 'is_active' => true];
        $items[] = ['label' => 'News', 'url' => '/news', 'is_active' => true];

        ($this->saveMenu)(MenuLocation::Explore, $items);
    }
}
