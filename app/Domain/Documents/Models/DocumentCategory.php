<?php

namespace App\Domain\Documents\Models;

use App\Support\ClearsPublicCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentCategory extends Model
{
    use ClearsPublicCache;

    protected $fillable = ['parent_id', 'name', 'slug', 'is_investor', 'sort_order'];

    /** @var array<string, string> */
    protected $casts = ['is_investor' => 'boolean'];

    /** @return BelongsTo<DocumentCategory, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<Document, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'category_id');
    }
}
