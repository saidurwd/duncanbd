<?php

namespace App\Domain\News\Models;

use App\Domain\Documents\Enums\DocumentStatus;
use App\Domain\Identity\Models\User;
use App\Domain\Media\Models\MediaItem;
use App\Domain\News\Enums\PostType;
use App\Support\ClearsPublicCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Post extends Model
{
    use ClearsPublicCache, LogsActivity, SoftDeletes;

    protected $fillable = [
        'type', 'category_id', 'title', 'slug', 'excerpt', 'body', 'featured_media_id',
        'tags', 'status', 'published_at', 'seo', 'author_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'type' => PostType::class,
        'status' => DocumentStatus::class,
        'tags' => 'array',
        'seo' => 'array',
        'published_at' => 'datetime',
    ];

    /** @return BelongsTo<NewsCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(NewsCategory::class, 'category_id');
    }

    /** @return BelongsTo<MediaItem, $this> */
    public function featured(): BelongsTo
    {
        return $this->belongsTo(MediaItem::class, 'featured_media_id');
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * @param  Builder<Post>  $query
     */
    public function scopeLive(Builder $query): void
    {
        $query->where('status', DocumentStatus::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function url(): string
    {
        return '/news/'.$this->published_at?->format('Y').'/'.$this->slug;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'type', 'status', 'published_at'])
            ->logOnlyDirty()
            ->useLogName('news');
    }
}
