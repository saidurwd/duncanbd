@props(['site'])
<nav class="utility-bar" aria-label="Group and tools">
    <div class="container">
        <span>
            @if ($site['group_name'] && $site['group_url'])
                <a href="{{ $site['group_url'] }}" rel="noopener">A {{ $site['group_name'] }} company</a>
            @endif
        </span>
        <div class="utility-bar__links">
            <a href="{{ route('search') }}">Search</a>
            @foreach ($site['socials'] as $label => $url)
                <a href="{{ $url }}" rel="noopener" target="_blank"><span class="visually-hidden">{{ $label }} (opens in a new tab)</span><span aria-hidden="true">{{ $label }}</span></a>
            @endforeach
        </div>
    </div>
</nav>
