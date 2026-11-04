<?php

use App\Support\GuestsOnlyCacheProfile;
use Spatie\ResponseCache\Hasher\DefaultHasher;
use Spatie\ResponseCache\Serializers\DefaultSerializer;

// spatie/laravel-responsecache 7.x (the line that runs on PHP 8.3).
return [
    'enabled' => env('RESPONSE_CACHE_ENABLED', true),

    // Public, session-less GET pages only (see GuestsOnlyCacheProfile).
    'cache_profile' => GuestsOnlyCacheProfile::class,

    'cache_bypass_header' => [
        'name' => env('CACHE_BYPASS_HEADER_NAME'),
        'value' => env('CACHE_BYPASS_HEADER_VALUE'),
    ],

    'cache_lifetime_in_seconds' => (int) env('RESPONSE_CACHE_LIFETIME', 60 * 60 * 24 * 7),

    'add_cache_time_header' => env('APP_DEBUG', false),
    'cache_time_header_name' => 'laravel-responsecache',
    'add_cache_age_header' => false,
    'cache_age_header_name' => 'laravel-responsecache-age',

    'cache_store' => env('RESPONSE_CACHE_DRIVER', 'file'),

    // None: cached pages are public, session-less and contain no CSRF token (forms live on uncached routes).
    'replacers' => [],

    'cache_tag' => env('RESPONSE_CACHE_TAG', ''),

    'hasher' => DefaultHasher::class,
    'serializer' => DefaultSerializer::class,
];
