<?php

namespace App\Domain\News;

use App\Domain\News\Models\Post;
use Illuminate\Database\Eloquent\Collection;

/** Read-side queries used by page blocks. */
class NewsQuery
{
    /** @return Collection<int, Post> */
    public function latest(int $limit = 3): Collection
    {
        return Post::live()->with(['category', 'featured.media'])
            ->orderByDesc('published_at')->limit(max(1, min($limit, 12)))->get();
    }
}
