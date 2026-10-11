<section class="block block-timeline">
    <ol>
        @foreach ($data['items'] ?? [] as $event)
            <li><strong>{{ $event['year'] }}</strong> <span>{{ $event['title'] }}</span>@if ($event['text'])<p>{{ $event['text'] }}</p>@endif</li>
        @endforeach
    </ol>
</section>
