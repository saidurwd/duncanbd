<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifyCsrfToken;

class VerifyCsrfToken extends BaseVerifyCsrfToken
{
    /** No JS-readable XSRF-TOKEN cookie: forms use the hidden _token field and the newsletter band an explicit header. */
    protected $addHttpCookie = false;
}
