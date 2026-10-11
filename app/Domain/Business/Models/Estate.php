<?php

namespace App\Domain\Business\Models;

use App\Domain\Media\Models\MediaItem;
use App\Support\ClearsPublicCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Estate extends Model
{
    use ClearsPublicCache, SoftDeletes;

    protected $fillable = ['business_id', 'name', 'slug', 'district', 'area_ha', 'factory', 'certifications', 'lat', 'lng', 'body', 'photo_media_id', 'sort_order', 'is_active'];

    /** @var array<string, string> */
    protected $casts = ['certifications' => 'array', 'is_active' => 'boolean', 'area_ha' => 'decimal:2', 'lat' => 'decimal:7', 'lng' => 'decimal:7'];

    /** @return BelongsTo<Business, $this> */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /** @return BelongsTo<MediaItem, $this> */
    public function photo(): BelongsTo
    {
        return $this->belongsTo(MediaItem::class, 'photo_media_id');
    }
}
