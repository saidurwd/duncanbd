<?php

namespace App\Domain\Settings\Actions;

use App\Domain\Media\Models\MediaFolder;
use App\Domain\Media\Models\MediaItem;
use App\Domain\Settings\SettingsRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Company logo (for the dark header/footer), an optional logo for light backgrounds, and the browser favicon.
 * Files go through the media library; the settings store only the public path and size, so the public layout
 * never has to query the media tables on a request.
 */
class UpdateBranding
{
    public const SLOTS = [
        'logo' => 'Logo (for the dark blue header and footer)',
        'logo_dark' => 'Logo for light backgrounds (optional: sign-in page)',
        'favicon' => 'Favicon (browser tab icon)',
        'share_image' => 'Default share image: shown when a page is shared on social media (1200 × 630 px)',
    ];

    public function __construct(private SettingsRepository $settings) {}

    /**
     * @param  array<string, UploadedFile|null>  $files  slot => new file
     * @param  list<string>  $remove  slots to clear
     */
    public function __invoke(array $files, array $remove, int $userId): void
    {
        $changed = [];

        foreach (array_keys(self::SLOTS) as $slot) {
            $file = $files[$slot] ?? null;

            if ($file instanceof UploadedFile) {
                $this->discard($slot);
                $this->settings->set('branding', $slot, $this->store($slot, $file, $userId));
                $changed[] = $slot;
            } elseif (in_array($slot, $remove, true)) {
                $this->discard($slot);
                $this->settings->set('branding', $slot, null);
                $changed[] = $slot;
            }
        }

        if ($changed !== []) {
            activity('settings')->causedBy(auth()->user())->withProperties(['branding' => $changed])->log('branding updated');
        }
    }

    /** @return array{id: int, path: string, width: int, height: int} */
    private function store(string $slot, UploadedFile $file, int $userId): array
    {
        [$width, $height] = getimagesize($file->getRealPath()) ?: [0, 0];

        $item = MediaItem::create([
            'folder_id' => MediaFolder::firstOrCreate(['name' => 'Branding'])->id,
            'title' => Str::headline($slot),
            'alt_text' => (string) $this->settings->get('identity', 'site_name', config('app.name')),
            'uploaded_by' => $userId,
        ]);

        $media = $item->addMedia($file)
            ->usingFileName(Str::random(24).'.'.$file->guessExtension())
            ->toMediaCollection(MediaItem::COLLECTION);

        // Site-relative path: keeps working whatever host or CDN serves the site.
        return ['id' => $item->id, 'path' => parse_url($media->getUrl(), PHP_URL_PATH), 'width' => $width, 'height' => $height];
    }

    /** Replaced or removed logos are deleted for good (files included): nothing else references them. */
    private function discard(string $slot): void
    {
        $old = $this->settings->get('branding', $slot);

        if (is_array($old) && isset($old['id'])) {
            MediaItem::withTrashed()->find($old['id'])?->forceDelete();
        }
    }
}
