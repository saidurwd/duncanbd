<?php

namespace App\Domain\People\Models;

use App\Domain\Media\Models\MediaItem;
use App\Support\ClearsPublicCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimelineEvent extends Model
{
    use ClearsPublicCache;

    protected $fillable = ['year', 'title', 'body', 'media_id', 'sort_order'];

    /** @return BelongsTo<MediaItem, $this> */
    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaItem::class, 'media_id');
    }
}
