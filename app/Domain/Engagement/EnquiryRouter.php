<?php

namespace App\Domain\Engagement;

use App\Domain\Engagement\Enums\EnquiryCategory;
use App\Domain\Settings\SettingsRepository;

/** Decides which inbox receives an enquiry: category rule, then general rule, then the site contact email. */
class EnquiryRouter
{
    public function __construct(private SettingsRepository $settings) {}

    public function addressFor(EnquiryCategory $category): string
    {
        foreach ([
            $this->settings->get('enquiries', $category->routeKey()),
            $this->settings->get('enquiries', EnquiryCategory::General->routeKey()),
            $this->settings->get('contact', 'email'),
            config('mail.from.address'),
        ] as $candidate) {
            if (filled($candidate) && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                return $candidate;
            }
        }

        return 'hello@example.com';
    }

    public function hrAddress(): string
    {
        $hr = $this->settings->get('enquiries', 'hr_email');

        return filled($hr) && filter_var($hr, FILTER_VALIDATE_EMAIL) ? $hr : $this->addressFor(EnquiryCategory::Careers);
    }
}
