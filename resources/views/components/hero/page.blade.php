@props(['title', 'intro' => null, 'crumbs' => []])
<section class="page-hero">
    <div class="container">
        @if (count($crumbs) > 1)
            <nav class="breadcrumb breadcrumb--on-dark" aria-label="Breadcrumb">
                <ol>@foreach ($crumbs as $crumb)<li>@if ($loop->last)<span aria-current="page">{{ $crumb['label'] }}</span>@else<a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>@endif</li>@endforeach</ol>
            </nav>
        @endif
        <h1>{{ $title }}</h1>
        @if ($intro)<p class="lead">{{ $intro }}</p>@endif
    </div>
</section>
