<?php

namespace App\Domain\Careers\Actions;

use App\Domain\Careers\Models\Vacancy;
use Illuminate\Support\Str;
use Mews\Purifier\Facades\Purifier;

class SaveVacancy
{
    /** @param array<string, mixed> $data validated input */
    public function __invoke(?Vacancy $vacancy, array $data): Vacancy
    {
        $vacancy ??= new Vacancy(['status' => 'draft']);

        $slug = Str::slug($data['slug'] ?? '') ?: Str::slug($data['title']);
        $base = $slug;
        for ($n = 2; Vacancy::withTrashed()->where('slug', $slug)->when($vacancy->id, fn ($q) => $q->where('id', '!=', $vacancy->id))->exists(); $n++) {
            $slug = "$base-$n";
        }

        if ($vacancy->exists && $vacancy->status->value === 'published') {
            $slug = $vacancy->slug; // public URL stays stable once live
        }

        $vacancy->fill([
            'title' => $data['title'],
            'slug' => $slug,
            'department' => $data['department'] ?? null,
            'location' => $data['location'] ?? null,
            'employment_type' => $data['employment_type'],
            'description' => Purifier::clean((string) ($data['description'] ?? '')),
            'requirements' => Purifier::clean((string) ($data['requirements'] ?? '')),
            'closing_date' => $data['closing_date'] ?? null,
        ])->save();

        return $vacancy;
    }
}
