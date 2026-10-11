<?php

namespace App\Console\Commands;

use App\Domain\Careers\Models\Application;
use App\Domain\Engagement\Enums\SubscriberStatus;
use App\Domain\Engagement\Models\Enquiry;
use App\Domain\Engagement\Models\Subscriber;
use App\Domain\Engagement\PersonalData;
use Illuminate\Console\Command;

class PruneStalePersonalData extends Command
{
    protected $signature = 'privacy:prune';

    protected $description = 'Apply data-retention rules to applications, enquiries and unconfirmed subscribers';

    public function handle(PersonalData $personalData): int
    {
        // Applications: N months after the vacancy closed (or, if never formally closed, after its closing date).
        $cutoff = now()->subMonths(config('privacy.applications_months_after_close'));
        $applications = 0;
        Application::with('vacancy')->whereHas('vacancy', fn ($q) => $q->withTrashed()
            ->where(fn ($w) => $w->where('closed_at', '<', $cutoff)->orWhere(fn ($x) => $x->whereNull('closed_at')->where('closing_date', '<', $cutoff->toDateString()))))
            ->each(function (Application $application) use ($personalData, &$applications) {
                $personalData->deleteApplication($application);
                $applications++;
            });

        $enquiries = Enquiry::where('created_at', '<', now()->subMonths(config('privacy.enquiries_months')))->delete();

        $subscribers = Subscriber::where('status', SubscriberStatus::Pending->value)
            ->where('updated_at', '<', now()->subDays(config('privacy.pending_subscriber_days')))->delete();

        $this->info("Deleted {$applications} applications, {$enquiries} enquiries, {$subscribers} unconfirmed subscribers.");

        return self::SUCCESS;
    }
}
