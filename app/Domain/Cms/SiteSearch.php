<?php

namespace App\Domain\Cms;

use App\Domain\Business\Models\Business;
use App\Domain\Cms\Models\Page;
use App\Domain\Documents\Models\Document;
use App\Domain\News\Models\Post;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Site search across live pages, news, documents and businesses.
 * Plain LIKE matching: fine at this content volume and portable; swap for Scout/Meilisearch if it outgrows that.
 */
class SiteSearch
{
    public const LIMIT = 40;

    /** @return Collection<int, array{title: string, url: string, type: string, excerpt: ?string, score: int}> */
    public function search(string $query): Collection
    {
        $query = trim(Str::limit($query, 100, ''));

        if (mb_strlen($query) < 2) {
            return new Collection;
        }

        $like = '%'.addcslashes($query, '%_\\').'%';
        $results = collect();

        foreach (Page::live()->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('blocks', 'like', $like))->limit(self::LIMIT)->get() as $page) {
            $results->push($this->row($page->title, RedirectManager::pageUrl($page), 'Page', $this->blocksText($page->blocks ?? []), $query));
        }

        foreach (Post::live()->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('excerpt', 'like', $like)->orWhere('body', 'like', $like))->limit(self::LIMIT)->get() as $post) {
            $results->push($this->row($post->title, $post->url(), $post->type->label(), $post->excerpt ?: strip_tags((string) $post->body), $query));
        }

        foreach (Document::live()->with('category')->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('description', 'like', $like))->limit(self::LIMIT)->get() as $doc) {
            $results->push($this->row($doc->title, route('documents.show', [$doc->category->slug, $doc->slug], false), 'Document', $doc->description, $query));
        }

        foreach (Business::where('is_active', true)->where(fn ($q) => $q->where('name', 'like', $like)->orWhere('summary', 'like', $like))->limit(self::LIMIT)->get() as $business) {
            $results->push($this->row($business->name, route('business.show', $business->slug, false), 'Business', $business->summary, $query));
        }

        return $results->sortByDesc('score')->values()->take(self::LIMIT);
    }

    /** @return array{title: string, url: string, type: string, excerpt: ?string, score: int} */
    private function row(string $title, string $url, string $type, ?string $text, string $query): array
    {
        return [
            'title' => $title,
            'url' => $url,
            'type' => $type,
            'excerpt' => $this->excerpt($text, $query),
            'score' => stripos($title, $query) !== false ? 2 : 1,
        ];
    }

    private function excerpt(?string $text, string $query): ?string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $text)));

        if ($text === '') {
            return null;
        }

        $pos = mb_stripos($text, $query);

        return Str::limit($pos === false ? $text : mb_substr($text, max(0, $pos - 60)), 180);
    }

    /** @param array<int, mixed> $blocks */
    private function blocksText(array $blocks): string
    {
        $parts = [];
        array_walk_recursive($blocks, function ($value, $key) use (&$parts) {
            if (is_string($value) && ! in_array($key, ['type', 'url', 'button_url', 'youtube_id', 'category'], true)) {
                $parts[] = $value;
            }
        });

        return implode(' ', $parts);
    }
}
