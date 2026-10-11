<?php

namespace App\Domain\Engagement\Enums;

enum EnquiryCategory: string
{
    case General = 'general';
    case Investor = 'investor';
    case Media = 'media';
    case Trade = 'trade';
    case Careers = 'careers';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::General => 'General enquiry',
            self::Investor => 'Investor / shareholder',
            self::Media => 'Media',
            self::Trade => 'Tea buyers & suppliers',
            self::Careers => 'Careers',
            self::Other => 'Something else',
        };
    }

    /** Settings key holding this category's destination inbox. */
    public function routeKey(): string
    {
        return 'route_'.$this->value;
    }
}
