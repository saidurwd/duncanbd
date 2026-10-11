<?php

namespace App\Domain\Cms\Actions;

use App\Domain\Cms\Enums\PageStatus;
use App\Domain\Cms\Events\PageContentChanged;
use App\Domain\Cms\Models\Page;
use App\Domain\Identity\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class TransitionPage
{
    public function __invoke(Page $page, PageStatus $target, User $user, ?CarbonInterface $publishAt = null, ?CarbonInterface $unpublishAt = null): Page
    {
        $allowed = $page->status->transitions();

        if (! isset($allowed[$target->value])) {
            throw ValidationException::withMessages([
                'status' => "A {$page->status->label()} page cannot move to {$target->label()}.",
            ]);
        }

        abort_unless($user->can($allowed[$target->value]), 403);

        if ($target === PageStatus::Scheduled && ($publishAt === null || $publishAt->isPast())) {
            throw ValidationException::withMessages(['published_at' => 'Choose a future date and time to schedule.']);
        }

        $attributes = ['status' => $target];

        if ($target === PageStatus::Scheduled) {
            $attributes['published_at'] = $publishAt;
        }

        if ($target === PageStatus::Published) {
            $attributes['published_at'] = $page->published_at && $page->published_at->isPast() ? $page->published_at : now();
        }

        if (in_array($target, [PageStatus::Published, PageStatus::Scheduled], true)) {
            $attributes['unpublish_at'] = $unpublishAt;
        }

        $page->update($attributes);

        PageContentChanged::dispatch($page);

        return $page;
    }
}
