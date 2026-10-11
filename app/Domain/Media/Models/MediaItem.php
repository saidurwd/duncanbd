<?php

namespace App\Domain\Media\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A library entry: editorial metadata (alt text, credit) wrapped around one stored image file.
 */
class MediaItem extends Model implements HasMedia
{
    use InteractsWithMedia, LogsActivity, SoftDeletes;

    public const COLLECTION = 'file';

    /** Allow-list: no SVG (script risk), no animated/unknown formats. */
    public const MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/avif'];

    protected $fillable = ['folder_id', 'title', 'alt_text', 'is_decorative', 'caption', 'credit', 'uploaded_by'];

    protected function casts(): array
    {
        return ['is_decorative' => 'boolean'];
    }

    /** @return BelongsTo<MediaFolder, $this> */
    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class, 'folder_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::COLLECTION)
            ->singleFile()
            ->acceptsMimeTypes(self::MIME_TYPES);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->performOnCollections(self::COLLECTION)->nonQueued()->width(400)->height(300)->format('webp');
        $this->addMediaConversion('medium')->performOnCollections(self::COLLECTION)->width(1200)->format('webp');
        $this->addMediaConversion('large')->performOnCollections(self::COLLECTION)->width(2400)->format('webp');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['title', 'alt_text', 'folder_id'])->logOnlyDirty()->useLogName('media');
    }

    /** Value for the HTML alt attribute: empty for decorative images. */
    public function altAttribute(): string
    {
        return $this->is_decorative ? '' : (string) $this->alt_text;
    }
}
