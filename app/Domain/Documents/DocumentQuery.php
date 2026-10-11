<?php

namespace App\Domain\Documents;

use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Models\DocumentCategory;
use Illuminate\Database\Eloquent\Collection;

/** Read-side queries used by page blocks. */
class DocumentQuery
{
    /** @return Collection<int, Document> */
    public function latest(?string $categorySlug, int $limit = 5): Collection
    {
        $category = $categorySlug ? DocumentCategory::where('slug', $categorySlug)->first() : null;

        if ($categorySlug && ! $category) {
            return new Collection;
        }

        return Document::live()->with(['category', 'currentVersion'])
            ->when($category, fn ($q) => $q->where('category_id', $category->id))
            ->orderByDesc('document_date')->orderByDesc('id')
            ->limit(max(1, min($limit, 50)))->get();
    }

    public function find(int $id): ?Document
    {
        return Document::live()->with(['category', 'currentVersion', 'cover.media'])->find($id);
    }
}
