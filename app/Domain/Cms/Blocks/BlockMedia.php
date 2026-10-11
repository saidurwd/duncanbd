<?php

namespace App\Domain\Cms\Blocks;

use App\Domain\Media\Models\MediaItem;
use Illuminate\Support\Collection;

/** Resolves every media id referenced by a set of blocks in a single query. */
class BlockMedia
{
    /**
     * @param  list<array<string, mixed>>  $blocks
     * @return Collection<int, MediaItem>
     */
    public static function forBlocks(array $blocks): Collection
    {
        $ids = [];
        array_walk_recursive($blocks, function ($value, $key) use (&$ids) {
            if (in_array($key, ['media_id', 'poster_media_id', 'og_media_id'], true) && is_int($value)) {
                $ids[] = $value;
            }
        });

        return $ids === []
            ? new Collection
            : MediaItem::with('media')->whereIn('id', array_unique($ids))->get()->keyBy('id');
    }
}
