<?php

namespace App\Domain\Content;

use App\Domain\Business\Models\Business;
use App\Domain\People\Models\Person;
use App\Domain\People\Models\TimelineEvent;
use Illuminate\Database\Eloquent\Collection;

/** Read-side queries used by page blocks. */
class ContentQuery
{
    /** @return Collection<int, Person> */
    public function people(?string $group): Collection
    {
        return Person::with('photo.media')->where('is_active', true)
            ->when(in_array($group, ['board', 'leadership'], true), fn ($q) => $q->where('group', $group))
            ->orderBy('sort_order')->orderBy('id')->get();
    }

    /** @return Collection<int, TimelineEvent> */
    public function timeline(): Collection
    {
        return TimelineEvent::orderBy('sort_order')->orderBy('year')->get();
    }

    /** @return Collection<int, Business> */
    public function businesses(): Collection
    {
        return Business::with('estates')->where('is_active', true)->orderBy('sort_order')->get();
    }
}
