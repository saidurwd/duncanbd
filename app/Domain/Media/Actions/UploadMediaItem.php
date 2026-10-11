<?php

namespace App\Domain\Media\Actions;

use App\Domain\Media\Models\MediaItem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class UploadMediaItem
{
    /** @param array{title?: ?string, alt_text?: ?string, is_decorative?: bool, folder_id?: ?int, caption?: ?string, credit?: ?string} $meta */
    public function __invoke(UploadedFile $file, array $meta, int $userId): MediaItem
    {
        $item = MediaItem::create([
            'title' => ($meta['title'] ?? null) ?: Str::of($file->getClientOriginalName())->beforeLast('.')->replace(['-', '_'], ' ')->toString(),
            'alt_text' => $meta['alt_text'] ?? null,
            'is_decorative' => (bool) ($meta['is_decorative'] ?? false),
            'folder_id' => $meta['folder_id'] ?? null,
            'caption' => $meta['caption'] ?? null,
            'credit' => $meta['credit'] ?? null,
            'uploaded_by' => $userId,
        ]);

        // Random stored filename: never trust or expose the client-supplied name.
        $item->addMedia($file)
            ->usingFileName(Str::random(40).'.'.$file->guessExtension())
            ->usingName($item->title)
            ->toMediaCollection(MediaItem::COLLECTION);

        return $item;
    }
}
