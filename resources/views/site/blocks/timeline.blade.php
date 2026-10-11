@inject('content', 'App\Domain\Content\ContentQuery')
@php($events = ! empty($data['items']) ? collect($data['items']) : $content->timeline()->map(fn ($e) => ['year' => $e->year, 'title' => $e->title, 'text' => $e->body]))
<section class="block block-timeline">
    <ol>
        @foreach ($events as $event)
            <li><strong>{{ $event['year'] }}</strong> <span>{{ $event['title'] }}</span>@if ($event['text'])<p>{{ $event['text'] }}</p>@endif</li>
        @endforeach
    </ol>
</section>
