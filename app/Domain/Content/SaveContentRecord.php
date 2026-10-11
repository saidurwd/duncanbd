<?php

namespace App\Domain\Content;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;

class SaveContentRecord
{
    /** @param array<string, mixed> $input validated input */
    public function __invoke(ContentResource $resource, ?Model $record, array $input): Model
    {
        $record ??= new ($resource->model);
        $values = [];

        foreach ($resource->fields as $key => $field) {
            $raw = $input[$key] ?? null;

            $values[$key] = match ($field['type']) {
                'richtext' => is_string($raw) ? Purifier::clean($raw) : null,
                'boolean' => (bool) $raw,
                'number', 'media' => ($raw === null || $raw === '') ? null : $raw + 0,
                'lines' => collect(preg_split('/\R/', (string) $raw))->map(fn ($l) => trim($l))->filter()->take(30)->values()->all(),
                'pairs' => collect(preg_split('/\R/', (string) $raw))->map(function ($line) {
                    [$label, $value] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');

                    return $label !== '' ? ['label' => $label, 'value' => $value] : null;
                })->filter()->take(10)->values()->all(),
                default => is_scalar($raw) && $raw !== '' ? $raw : null,
            };
        }

        // sort_order/is_active defaults when the type has them but the form omitted them.
        if (array_key_exists('sort_order', $values)) {
            $values['sort_order'] ??= 0;
        }

        if ($resource->slugFrom && ! $record->exists) {
            $values['slug'] = $this->uniqueSlug($resource, Str::slug((string) $values[$resource->slugFrom]), $values);
        }

        $record->fill($values)->save();

        return $record;
    }

    /** @param array<string, mixed> $values */
    private function uniqueSlug(ContentResource $resource, string $base, array $values): string
    {
        $base = $base !== '' ? $base : 'item';
        $slug = $base;

        for ($n = 2; $this->slugTaken($resource, $slug, $values); $n++) {
            $slug = "$base-$n";
        }

        return $slug;
    }

    /** @param array<string, mixed> $values */
    private function slugTaken(ContentResource $resource, string $slug, array $values): bool
    {
        // Include trashed rows: the unique index still counts them.
        $query = $resource->model::query()->withoutGlobalScope(SoftDeletingScope::class)->where('slug', $slug);

        // Estate slugs are unique within their business only.
        if (isset($values['business_id'])) {
            $query->where('business_id', $values['business_id']);
        }

        return $query->exists();
    }
}
