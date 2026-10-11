<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

class VerifyCsrfToken extends PreventRequestForgery
{
    /** No JS-readable XSRF-TOKEN cookie: forms use the hidden _token field and the newsletter band an explicit header. */
    protected $addHttpCookie = false;
}
