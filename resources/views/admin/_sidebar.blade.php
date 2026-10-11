@php
    $is = fn (...$patterns) => request()->routeIs(...$patterns);
    $link = fn (string $route, string $icon, string $label, bool $active, ?string $param = null) =>
        '<li class="nav-item"><a href="'.e($param ? route($route, $param) : route($route)).'" class="nav-link '.($active ? 'active' : '').'"'.($active ? ' aria-current="page"' : '').'><i class="nav-icon bi '.$icon.'"></i><p>'.$label.'</p></a></li>';
    $contentOpen = $is('admin.content.*');
@endphp
<ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
    {!! $link('admin.dashboard', 'bi-speedometer2', 'Dashboard', $is('admin.dashboard')) !!}

    @canany(['pages.edit', 'news.edit', 'documents.investor.manage', 'documents.general.manage', 'media.manage', 'media.view', 'pages.publish'])
        <li class="nav-header">CONTENT</li>
        @can('pages.edit'){!! $link('admin.pages.index', 'bi-file-earmark-text', 'Pages', $is('admin.pages.*')) !!}@endcan
        @can('news.edit'){!! $link('admin.posts.index', 'bi-newspaper', 'News &amp; press', $is('admin.posts.*')) !!}@endcan
        @canany(['documents.investor.manage', 'documents.general.manage']){!! $link('admin.documents.index', 'bi-file-earmark-pdf', 'Documents', $is('admin.documents.*')) !!}@endcanany
        @canany(['media.manage', 'media.view']){!! $link('admin.media.index', 'bi-images', 'Media library', $is('admin.media.*')) !!}@endcanany
        @can('pages.publish')
            <li class="nav-item @if ($contentOpen) menu-open @endif">
                <a href="#" class="nav-link @if ($contentOpen) active @endif"><i class="nav-icon bi bi-building"></i><p>Company content<i class="nav-arrow bi bi-chevron-right"></i></p></a>
                <ul class="nav nav-treeview">
                    @foreach (App\Domain\Content\ContentResource::all() as $res)
                        <li class="nav-item"><a href="{{ route('admin.content.index', $res->key) }}" class="nav-link @if (request()->is(config('admin.path').'/content/'.$res->key.'*')) active @endif"><i class="nav-icon bi bi-circle"></i><p>{{ $res->label }}</p></a></li>
                    @endforeach
                </ul>
            </li>
        @endcan
    @endcanany

    @canany(['enquiries.manage', 'enquiries.view', 'careers.manage'])
        <li class="nav-header">ENGAGEMENT</li>
        @canany(['enquiries.manage', 'enquiries.view']){!! $link('admin.enquiries.index', 'bi-envelope', 'Enquiries', $is('admin.enquiries.*')) !!}{!! $link('admin.subscribers.index', 'bi-people', 'Subscribers', $is('admin.subscribers.*')) !!}@endcanany
        @can('careers.manage'){!! $link('admin.vacancies.index', 'bi-briefcase', 'Careers', $is('admin.vacancies.*', 'admin.applications.*')) !!}@endcan
    @endcanany

    @canany(['menus.manage', 'seo.manage'])
        <li class="nav-header">SITE</li>
        @can('menus.manage'){!! $link('admin.menus.index', 'bi-list-nested', 'Menus', $is('admin.menus.*')) !!}@endcan
        @can('seo.manage'){!! $link('admin.redirects.index', 'bi-signpost-split', 'Redirects &amp; 404s', $is('admin.redirects.*')) !!}@endcan
    @endcanany

    @canany(['users.manage', 'settings.manage'])
        <li class="nav-header">ADMINISTRATION</li>
        @can('users.manage'){!! $link('admin.users.index', 'bi-person-gear', 'Users &amp; roles', $is('admin.users.*')) !!}@endcan
        @can('settings.manage'){!! $link('admin.settings.edit', 'bi-gear', 'Settings', $is('admin.settings.*')) !!}{!! $link('admin.privacy.index', 'bi-person-lock', 'Privacy tools', $is('admin.privacy.*')) !!}@endcan
    @endcanany

    <li class="nav-header">ACCOUNT</li>
    {!! $link('admin.security', 'bi-shield-lock', 'My security', $is('admin.security')) !!}
</ul>
