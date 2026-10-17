<?php

namespace App\View\Composers;

use App\Domain\Cms\Enums\MenuLocation;
use App\Domain\Cms\MenuRepository;
use App\Domain\Settings\SettingsRepository;
use Illuminate\Contracts\View\View;

/** Shares site-wide chrome data (identity, contacts, menus, structured data) with the public layout. */
class SiteComposer
{
    public function __construct(private SettingsRepository $settings, private MenuRepository $menus) {}

    public function compose(View $view): void
    {
        $get = fn (string $group, string $key, mixed $default = null) => $this->settings->get($group, $key, $default);

        $name = $get('identity', 'site_name') ?: config('app.name');
        $socials = array_filter([
            'LinkedIn' => $get('social', 'linkedin'),
            'Facebook' => $get('social', 'facebook'),
            'YouTube' => $get('social', 'youtube'),
        ]);

        $organization = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $name,
            'url' => url('/'),
            'email' => $get('contact', 'email'),
            'telephone' => $get('contact', 'phone'),
            'sameAs' => array_values($socials) ?: null,
            'parentOrganization' => $get('identity', 'group_name') ? array_filter([
                '@type' => 'Organization',
                'name' => $get('identity', 'group_name'),
                'url' => $get('identity', 'group_url'),
            ]) : null,
        ]);

        $view->with('site', [
            'name' => $name,
            'tagline' => $get('identity', 'tagline'),
            'default_description' => $get('seo', 'default_description') ?: $get('identity', 'tagline') ?: $name.' — corporate website',
            'group_name' => $get('identity', 'group_name'),
            'group_url' => $get('identity', 'group_url'),
            'email' => $get('contact', 'email'),
            'phone' => $get('contact', 'phone'),
            'fax' => $get('contact', 'fax'),
            'addresses' => array_filter([$get('contact', 'address_dhaka'), $get('contact', 'address_chattogram')]),
            'socials' => $socials,
            'footer_text' => $get('footer', 'footer_text'),
            'cookie_text' => $get('footer', 'cookie_banner_text') ?: 'We use cookies to understand how the site is used. Necessary cookies are always on; analytics cookies are only set if you accept.',
            'analytics_id' => $get('footer', 'analytics_id'),
            'menu_explore' => $this->menus->tree(MenuLocation::Explore),
            'menu_footer' => $this->menus->tree(MenuLocation::Footer),
            'menu_legal' => $this->menus->tree(MenuLocation::Legal),
            'organization_ld' => $organization,
        ]);
    }
}
