<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Lightweight bot defence for public forms: a hidden honeypot field, a signed time trap
 * (humans take more than a few seconds), and optional Cloudflare Turnstile.
 */
class FormGuard
{
    public const HONEYPOT = 'website';

    public const MIN_SECONDS = 3;

    public const MAX_SECONDS = 7200;

    public function issueTimestamp(): string
    {
        return Crypt::encryptString((string) now()->timestamp);
    }

    public function passes(Request $request): bool
    {
        if (filled($request->input(self::HONEYPOT))) {
            return false;
        }

        try {
            $issued = (int) Crypt::decryptString((string) $request->input('_ts'));
        } catch (Throwable) {
            return false;
        }

        $age = now()->timestamp - $issued;

        return $age >= self::MIN_SECONDS && $age <= self::MAX_SECONDS;
    }

    public function turnstileEnabled(): bool
    {
        return filled(config('services.turnstile.secret')) && filled(config('services.turnstile.site_key'));
    }

    public function turnstilePasses(Request $request): bool
    {
        if (! $this->turnstileEnabled()) {
            return true;
        }

        $token = (string) $request->input('cf-turnstile-response');

        if ($token === '') {
            return false;
        }

        try {
            return (bool) Http::asForm()->timeout(5)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => config('services.turnstile.secret'),
                'response' => $token,
                'remoteip' => $request->ip(),
            ])->json('success');
        } catch (Throwable) {
            return false; // fail closed
        }
    }
}
