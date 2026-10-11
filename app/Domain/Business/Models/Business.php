<?php

namespace App\Domain\Business\Models;

use App\Domain\Media\Models\MediaItem;
use App\Support\ClearsPublicCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Business extends Model
{
    use ClearsPublicCache, SoftDeletes;

    protected $fillable = ['name', 'slug', 'summary', 'body', 'metrics', 'logo_media_id', 'website_url', 'sort_order', 'is_active'];

    /** @var array<string, string> */
    protected $casts = ['metrics' => 'array', 'is_active' => 'boolean'];

    /** @return HasMany<Estate, $this> */
    public function estates(): HasMany
    {
        return $this->hasMany(Estate::class)->orderBy('sort_order');
    }

    /** @return BelongsTo<MediaItem, $this> */
    public function logo(): BelongsTo
    {
        return $this->belongsTo(MediaItem::class, 'logo_media_id');
    }
}
