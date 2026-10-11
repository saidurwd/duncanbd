<section class="block block-text">
    @if ($data['heading'])<h2>{{ $data['heading'] }}</h2>@endif
    {{-- Sanitised with the HTMLPurifier allow-list when the page is saved. --}}
    <div class="prose">{!! $data['body'] !!}</div>
</section>
