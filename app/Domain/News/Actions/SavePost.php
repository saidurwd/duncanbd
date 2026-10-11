<?php

namespace App\Domain\News\Actions;

use App\Domain\Identity\Models\User;
use App\Domain\News\Models\Post;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;

class SavePost
{
    /** @param array<string, mixed> $data validated input */
    public function __invoke(?Post $post, array $data, User $user): Post
    {
        $post ??= new Post(['author_id' => $user->id, 'status' => 'draft']);

        $slug = Str::slug($data['slug'] ?? '') ?: Str::slug($data['title']);
        $slug = $this->uniqueSlug($slug, $post->id);

        // Slug is part of the public URL: frozen once the post has been published.
        if ($post->exists && $post->status->value === 'published') {
            $slug = $post->slug;
        }

        $tags = collect(explode(',', (string) ($data['tags'] ?? '')))
            ->map(fn ($t) => Str::limit(trim($t), 40, ''))->filter()->unique()->take(15)->values()->all();

        $post->fill([
            'type' => $data['type'],
            'category_id' => $data['category_id'] ?? null,
            'title' => $data['title'],
            'slug' => $slug,
            'excerpt' => $data['excerpt'] ?? null,
            'body' => Purifier::clean((string) ($data['body'] ?? '')),
            'featured_media_id' => $data['featured_media_id'] ?? null,
            'tags' => $tags,
            'seo' => ['title' => $data['seo']['title'] ?? null, 'description' => $data['seo']['description'] ?? null],
        ])->save();

        return $post;
    }

    private function uniqueSlug(string $slug, ?int $ignoreId): string
    {
        $candidate = $slug;
        for ($n = 2; Post::withTrashed()->where('slug', $candidate)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists(); $n++) {
            $candidate = "$slug-$n";
        }

        return $candidate;
    }
}
