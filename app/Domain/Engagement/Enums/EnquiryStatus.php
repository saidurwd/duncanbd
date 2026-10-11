<?php

namespace App\Domain\Engagement\Enums;

enum EnquiryStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Spam = 'spam';

    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }
}
