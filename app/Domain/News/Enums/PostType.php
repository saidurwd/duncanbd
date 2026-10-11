<?php

namespace App\Domain\News\Enums;

enum PostType: string
{
    case News = 'news';
    case Press = 'press';

    public function label(): string
    {
        return $this === self::News ? 'News' : 'Press release';
    }
}
