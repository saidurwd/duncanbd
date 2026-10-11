<?php

namespace App\Domain\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItem extends Model
{
    protected $fillable = ['menu_id', 'parent_id', 'label', 'page_id', 'url', 'target', 'sort_order', 'is_active'];

    /** @var array<string, string> */
    protected $casts = ['is_active' => 'boolean'];

    /** @return BelongsTo<Page, $this> */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
