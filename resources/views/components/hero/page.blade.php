@props(['title', 'intro' => null])
<section class="page-hero">
    <div class="container">
        <h1>{{ $title }}</h1>
        @if ($intro)<p class="lead">{{ $intro }}</p>@endif
    </div>
</section>
