<?php

namespace App\Domain\Documents\Enums;

enum DocumentStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';
}
