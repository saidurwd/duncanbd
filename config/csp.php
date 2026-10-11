<?php

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Nonce\RandomString;
use Spatie\Csp\Presets\Basic;

return [

    /*
     * Presets will determine which CSP headers will be set. A valid CSP preset is
     * any class that implements `Spatie\Csp\Preset`
     */
    'presets' => [
        Basic::class,
    ],

    /**
     * Register additional global CSP directives here.
     */
    'directives' => [
        // Clickjacking protection that CSP's default-src does not cover (X-Frame-Options is kept for old browsers).
        [Directive::FRAME_ANCESTORS, Keyword::SELF],
        // Inline SVG data URIs (Bootstrap form-select arrows etc.). Images cannot execute script.
        [Directive::IMG, 'data:'],
        // Click-to-load video (privacy-enhanced YouTube domain).
        [Directive::FRAME, 'https://www.youtube-nocookie.com'],
        // Google Analytics: the script is only injected after the visitor accepts cookies, and
        // these hosts are only allowed when ANALYTICS is switched on in the environment.
        ...(env('CSP_ALLOW_ANALYTICS', false) ? [
            [Directive::SCRIPT, 'https://www.googletagmanager.com'],
            [Directive::IMG, ['https://www.google-analytics.com', 'https://www.googletagmanager.com']],
            [Directive::CONNECT, ['https://www.google-analytics.com', 'https://*.google-analytics.com', 'https://*.analytics.google.com', 'https://www.googletagmanager.com']],
        ] : []),
        // Cloudflare Turnstile widget (only when keys are configured).
        ...(env('TURNSTILE_SITE_KEY') ? [
            [Directive::SCRIPT, 'https://challenges.cloudflare.com'],
            [Directive::FRAME, 'https://challenges.cloudflare.com'],
        ] : []),
    ],

    /*
     * These presets which will be put in a report-only policy. This is great for testing out
     * a new policy or changes to existing CSP policy without breaking anything.
     */
    'report_only_presets' => [
        //
    ],

    /**
     * Register additional global report-only CSP directives here.
     */
    'report_only_directives' => [
        // [Directive::SCRIPT, [Keyword::UNSAFE_EVAL, Keyword::UNSAFE_INLINE]],
    ],

    /*
     * All violations against a policy will be reported to this url.
     * A great service you could use for this is https://report-uri.com/
     */
    'report_uri' => env('CSP_REPORT_URI', ''),

    /*
     * Optional separate report url for the report-only policy. When empty,
     * the report-only policy falls back to `report_uri` above. Useful for
     * services like report-uri.com that require different paths for enforcing
     * (`/enforce`) and report-only (`/reportOnly`) policies.
     */
    'report_only_uri' => env('CSP_REPORT_ONLY_URI', ''),

    /*
     * The name of the reporting endpoint that violations should be sent to.
     * The endpoint itself must be defined in `reporting_endpoints` below.
     */
    'report_to' => env('CSP_REPORT_TO', ''),

    /*
     * Optional separate reporting endpoint name for the report-only policy.
     * When empty, the report-only policy falls back to `report_to` above.
     */
    'report_only_to' => env('CSP_REPORT_ONLY_TO', ''),

    /*
     * Reporting endpoints that will be sent in the `Reporting-Endpoints` HTTP
     * header. The keys are the endpoint names that can be referenced from
     * `report_to` above.
     *
     * Example: ['default' => 'https://example.com/csp-reports']
     */
    'reporting_endpoints' => [
        //
    ],

    /*
     * Headers will only be added if this setting is set to true.
     */
    'enabled' => env('CSP_ENABLED', true),

    /**
     * Headers will be added when Vite is hot reloading.
     */
    'enabled_while_hot_reloading' => env('CSP_ENABLED_WHILE_HOT_RELOADING', false),

    /*
     * The class responsible for generating the nonces used in inline tags and headers.
     */
    'nonce_generator' => RandomString::class,

    /*
     * Set false to disable automatic nonce generation and handling.
     * This is useful when you want to use 'unsafe-inline' for scripts/styles
     * and cannot add inline nonces.
     * Note that this will make your CSP policy less secure.
     */
    'nonce_enabled' => env('CSP_NONCE_ENABLED', true),
];
