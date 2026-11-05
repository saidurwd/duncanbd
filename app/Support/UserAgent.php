<?php

namespace App\Support;

/** Turns a raw User-Agent header into "Chrome on macOS". Good enough for an audit table; not a full parser. */
class UserAgent
{
    public static function summary(?string $agent): string
    {
        if (! $agent) {
            return 'Unknown';
        }

        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Edge',
            str_contains($agent, 'OPR/') || str_contains($agent, 'Opera') => 'Opera',
            str_contains($agent, 'Firefox/') => 'Firefox',
            str_contains($agent, 'Chrome/') => 'Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            str_contains($agent, 'curl') => 'curl',
            str_contains($agent, 'Symfony') => 'Test client',
            default => 'Other browser',
        };

        $os = match (true) {
            str_contains($agent, 'iPhone') || str_contains($agent, 'iPad') => 'iOS',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Mac OS X') || str_contains($agent, 'Macintosh') => 'macOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => null,
        };

        return $os ? "$browser on $os" : $browser;
    }
}
