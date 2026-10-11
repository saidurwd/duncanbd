<?php

namespace App\Domain\People\Models;

use App\Domain\Media\Models\MediaItem;
use App\Support\ClearsPublicCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model
{
    use ClearsPublicCache, SoftDeletes;

    protected $table = 'people';

    protected $fillable = ['name', 'slug', 'position', 'group', 'bio', 'photo_media_id', 'sort_order', 'is_active'];

    /** @var array<string, string> */
    protected $casts = ['is_active' => 'boolean'];

    /** @return BelongsTo<MediaItem, $this> */
    public function photo(): BelongsTo
    {
        return $this->belongsTo(MediaItem::class, 'photo_media_id');
    }
}
