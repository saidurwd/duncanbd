<?php

namespace App\Domain\Documents\Actions;

use App\Domain\Documents\Models\Document;
use App\Domain\Identity\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaveDocument
{
    public function __construct(private StoreDocumentVersion $storeVersion) {}

    /**
     * @param  array<string, mixed>  $data  validated metadata
     */
    public function __invoke(?Document $document, array $data, ?UploadedFile $file, User $user): Document
    {
        return DB::transaction(function () use ($document, $data, $file, $user) {
            $document ??= new Document(['created_by' => $user->id, 'status' => 'draft']);

            $slug = Str::slug($data['slug'] ?? '') ?: Str::slug($data['title']);
            $slug = $this->uniqueSlug($slug, (int) $data['category_id'], $document->id);

            // Published slugs are immutable: external links and search results depend on them.
            if ($document->exists && $document->status->value === 'published') {
                $slug = $document->slug;
            }

            $document->fill([
                'category_id' => $data['category_id'],
                'title' => $data['title'],
                'slug' => $slug,
                'description' => $data['description'] ?? null,
                'financial_year' => $data['financial_year'] ?? null,
                'document_date' => $data['document_date'] ?? null,
                'visibility' => $data['visibility'],
                'published_at' => $data['published_at'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
                'cover_media_id' => $data['cover_media_id'] ?? null,
            ])->save();

            if ($file) {
                ($this->storeVersion)($document, $file, $user);
            }

            return $document;
        });
    }

    private function uniqueSlug(string $slug, int $categoryId, ?int $ignoreId): string
    {
        $candidate = $slug;
        $n = 2;

        while (Document::withTrashed()->where('category_id', $categoryId)->where('slug', $candidate)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $candidate = $slug.'-'.$n++;
        }

        return $candidate;
    }
}
