<?php

namespace App\Domain\Documents\Models;

use App\Domain\Documents\Enums\DocumentStatus;
use App\Domain\Documents\Enums\Visibility;
use App\Domain\Identity\Models\User;
use App\Domain\Media\Models\MediaItem;
use App\Support\ClearsPublicCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Document extends Model
{
    use ClearsPublicCache, LogsActivity, SoftDeletes;

    protected $fillable = [
        'uuid', 'category_id', 'title', 'slug', 'description', 'financial_year', 'document_date',
        'visibility', 'status', 'published_at', 'expires_at', 'cover_media_id', 'created_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'status' => DocumentStatus::class,
        'visibility' => Visibility::class,
        'document_date' => 'date',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Document $document) {
            $document->uuid ??= (string) Str::uuid();
        });
    }

    /** @return BelongsTo<DocumentCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(DocumentCategory::class, 'category_id');
    }

    /** @return HasMany<DocumentVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)->orderByDesc('version');
    }

    /** @return HasOne<DocumentVersion, $this> */
    public function currentVersion(): HasOne
    {
        return $this->hasOne(DocumentVersion::class)->where('is_current', true);
    }

    /** @return BelongsTo<MediaItem, $this> */
    public function cover(): BelongsTo
    {
        return $this->belongsTo(MediaItem::class, 'cover_media_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Publicly downloadable: published, public, inside its publish window.
     *
     * @param  Builder<Document>  $query
     */
    public function scopeLive(Builder $query): void
    {
        $query->where('status', DocumentStatus::Published->value)
            ->where('visibility', Visibility::Public->value)
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function isLive(): bool
    {
        return $this->status === DocumentStatus::Published
            && $this->visibility === Visibility::Public
            && ($this->published_at === null || $this->published_at->isPast())
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function fileTypeLabel(): string
    {
        return strtoupper(pathinfo((string) $this->currentVersion?->original_name, PATHINFO_EXTENSION));
    }

    public function sizeLabel(): string
    {
        return Number::fileSize((int) $this->currentVersion?->size);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'category_id', 'visibility', 'status', 'published_at', 'expires_at', 'financial_year'])
            ->logOnlyDirty()
            ->useLogName('documents');
    }
}
