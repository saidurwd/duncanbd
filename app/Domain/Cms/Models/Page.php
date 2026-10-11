<?php

namespace App\Domain\Cms\Models;

use App\Domain\Cms\Enums\PageStatus;
use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Page extends Model
{
    use LogsActivity, SoftDeletes;

    protected $fillable = [
        'parent_id', 'title', 'slug', 'path', 'template', 'blocks', 'status',
        'published_at', 'unpublish_at', 'seo', 'author_id', 'sort_order',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'blocks' => 'array',
        'seo' => 'array',
        'status' => PageStatus::class,
        'published_at' => 'datetime',
        'unpublish_at' => 'datetime',
    ];

    /** @return BelongsTo<Page, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<Page, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return MorphMany<Revision, $this> */
    public function revisions(): MorphMany
    {
        return $this->morphMany(Revision::class, 'revisionable')->latest('id');
    }

    /**
     * Live to the public: published status and inside its publish window.
     *
     * @param  Builder<Page>  $query
     */
    public function scopeLive(Builder $query): void
    {
        $query->where('status', PageStatus::Published->value)
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('unpublish_at')->orWhere('unpublish_at', '>', now()));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'slug', 'path', 'status', 'published_at', 'unpublish_at'])
            ->logOnlyDirty()
            ->useLogName('pages');
    }
}
