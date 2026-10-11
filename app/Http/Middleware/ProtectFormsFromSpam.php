<?php

namespace App\Http\Middleware;

use App\Support\FormGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Silently drops bot submissions (they get the same "thank you" a human would) so they learn nothing. */
class ProtectFormsFromSpam
{
    public function __construct(private FormGuard $guard) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->guard->passes($request)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Thank you. Your message has been received.']);
            }

            return back()->with('status', 'Thank you. Your message has been received.');
        }

        if (! $this->guard->turnstilePasses($request)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'We could not verify you are human. Please try again.'], 422);
            }

            return back()->withInput()->withErrors(['captcha' => 'We could not verify you are human. Please try again.']);
        }

        return $next($request);
    }
}
