<?php

namespace App\Domain\Careers\Enums;

enum EmploymentType: string
{
    case FullTime = 'full_time';
    case PartTime = 'part_time';
    case Contract = 'contract';
    case Internship = 'internship';
    case Graduate = 'graduate';

    public function label(): string
    {
        return ucwords(str_replace('_', ' ', $this->value));
    }

    /** schema.org employmentType value. */
    public function schemaValue(): string
    {
        return match ($this) {
            self::FullTime, self::Graduate => 'FULL_TIME',
            self::PartTime => 'PART_TIME',
            self::Contract => 'CONTRACTOR',
            self::Internship => 'INTERN',
        };
    }
}
