<?php

namespace App\Support;

use App\Domain\Cms\Events\PublicContentChanged;

/** Add to any model whose changes alter public pages: saving or deleting it clears the guest cache. */
trait ClearsPublicCache
{
    public static function bootClearsPublicCache(): void
    {
        static::saved(fn () => PublicContentChanged::dispatch());
        static::deleted(fn () => PublicContentChanged::dispatch());
    }
}
