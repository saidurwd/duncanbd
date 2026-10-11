<?php

use App\Support\Format;

it('formats file sizes without needing the intl extension', function () {
    expect(Format::fileSize(0))->toBe('0 B')
        ->and(Format::fileSize(512))->toBe('512 B')
        ->and(Format::fileSize(2048))->toBe('2 KB')
        ->and(Format::fileSize(1536))->toBe('1.5 KB')
        ->and(Format::fileSize(13294308))->toBe('12.7 MB')
        ->and(Format::fileSize(5 * 1024 ** 3))->toBe('5 GB');
});
