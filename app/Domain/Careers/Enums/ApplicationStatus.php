<?php

namespace App\Domain\Careers\Enums;

enum ApplicationStatus: string
{
    case New = 'new';
    case Shortlisted = 'shortlisted';
    case Interview = 'interview';
    case Offered = 'offered';
    case Hired = 'hired';
    case Rejected = 'rejected';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
