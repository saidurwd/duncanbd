<?php

namespace App\Support;

class Format
{
    /** Human file size ("1.4 MB"). Plain PHP so it works without the intl extension. */
    public static function fileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = $bytes > 0 ? min((int) floor(log($bytes, 1024)), count($units) - 1) : 0;
        $value = $bytes / (1024 ** $i);

        return ($i === 0 ? (string) $bytes : rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.')).' '.$units[$i];
    }
}
