<?php

namespace App\Console\Commands;

use App\Domain\Engagement\PersonalData;
use Illuminate\Console\Command;

class ErasePersonalData extends Command
{
    protected $signature = 'privacy:erase {email} {--force : Skip the confirmation prompt}';

    protected $description = 'Erase all enquiries, applications (and CV files) and the subscription for an email address';

    public function handle(PersonalData $personalData): int
    {
        $email = (string) $this->argument('email');
        $counts = $personalData->counts($email);

        $this->line(json_encode($counts));

        if (! $this->option('force') && ! $this->confirm("Erase all of the above for {$email}?")) {
            return self::FAILURE;
        }

        $personalData->erase($email);
        activity('privacy')->withProperties(['via' => 'cli', 'email_hash' => sha1(strtolower($email))])->log('personal data erased');
        $this->info('Erased.');

        return self::SUCCESS;
    }
}
