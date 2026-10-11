@props(['items'])
@if (count($items) > 1)
    <nav class="breadcrumb container" aria-label="Breadcrumb">
        <ol>
            @foreach ($items as $crumb)
                <li>@if ($loop->last)<span aria-current="page">{{ $crumb['label'] }}</span>@else<a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>@endif</li>
            @endforeach
        </ol>
    </nav>
@endif
