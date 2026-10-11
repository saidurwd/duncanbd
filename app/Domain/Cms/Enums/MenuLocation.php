<?php

namespace App\Domain\Cms\Enums;

enum MenuLocation: string
{
    case Explore = 'explore';
    case Footer = 'footer';
    case Legal = 'legal';

    public function label(): string
    {
        return match ($this) {
            self::Explore => 'Header "Explore" menu',
            self::Footer => 'Footer links',
            self::Legal => 'Legal links',
        };
    }
}
