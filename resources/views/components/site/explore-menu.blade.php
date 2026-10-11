@props(['items'])
<div id="explore-menu" class="explore-menu" data-explore-menu role="dialog" aria-modal="true" aria-label="Site menu" hidden>
    <div class="container">
        <div class="explore-menu__bar"><span class="explore-menu__title">{{ config('app.name') }}</span><button type="button" class="explore-menu__close" data-explore-close>Close menu</button></div>
        <nav aria-label="Main">
            <ul class="explore-menu__top explore-menu__grid">
                @foreach ($items as $item)
                    <li>
                        <a href="{{ $item['url'] ?? '#' }}" @if ($item['target'] === '_blank') target="_blank" rel="noopener" @endif>{{ $item['label'] }}</a>
                        @if ($item['children'])
                            <ul class="explore-menu__sub">
                                @foreach ($item['children'] as $child)
                                    <li><a href="{{ $child['url'] ?? '#' }}" @if ($child['target'] === '_blank') target="_blank" rel="noopener" @endif>{{ $child['label'] }}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        </nav>
    </div>
</div>
