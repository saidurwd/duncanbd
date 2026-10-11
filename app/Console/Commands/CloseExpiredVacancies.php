<?php

namespace App\Console\Commands;

use App\Domain\Careers\Models\Vacancy;
use App\Domain\Documents\Enums\DocumentStatus;
use Illuminate\Console\Command;

class CloseExpiredVacancies extends Command
{
    protected $signature = 'careers:close-expired';

    protected $description = 'Close published vacancies whose closing date has passed';

    public function handle(): int
    {
        $count = 0;

        foreach (Vacancy::where('status', DocumentStatus::Published->value)->whereNotNull('closing_date')->where('closing_date', '<', now('Asia/Dhaka')->toDateString())->get() as $vacancy) {
            $vacancy->update(['status' => DocumentStatus::Archived, 'closed_at' => $vacancy->closing_date->endOfDay()]);
            $count++;
        }

        $this->info("Closed {$count} vacancies.");

        return self::SUCCESS;
    }
}
