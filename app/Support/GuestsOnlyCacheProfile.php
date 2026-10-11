<?php

namespace App\Support;

use Illuminate\Http\Request;
use Spatie\ResponseCache\CacheProfiles\CacheAllSuccessfulGetRequests;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public pages only. Anything that could carry per-user state is never cached:
 * authenticated requests, responses that set cookies, or non-200 results.
 */
class GuestsOnlyCacheProfile extends CacheAllSuccessfulGetRequests
{
    public function shouldCacheRequest(Request $request): bool
    {
        return parent::shouldCacheRequest($request)
            && ! $request->user()
            && ! $request->hasCookie(config('session.cookie'));
    }

    public function shouldCacheResponse(Response $response): bool
    {
        return parent::shouldCacheResponse($response)
            && $response->getStatusCode() === 200
            && $response->headers->getCookies() === [];
    }
}
