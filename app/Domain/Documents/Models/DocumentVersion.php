<?php

namespace App\Domain\Documents\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVersion extends Model
{
    protected $fillable = ['document_id', 'version', 'path', 'original_name', 'mime', 'size', 'checksum', 'uploaded_by', 'is_current'];

    /** @var array<string, string> */
    protected $casts = ['is_current' => 'boolean'];

    /** @return BelongsTo<Document, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
