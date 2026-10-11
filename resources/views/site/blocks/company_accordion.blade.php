@inject('content', 'App\Domain\Content\ContentQuery')
@php
    $companies = ! empty($data['items'])
        ? collect($data['items'])
        : $content->businesses()->map(fn ($b) => [
            'name' => $b->name, 'description' => $b->summary,
            'metric_1' => isset($b->metrics[0]) ? $b->metrics[0]['label'].': '.$b->metrics[0]['value'] : null,
            'metric_2' => isset($b->metrics[1]) ? $b->metrics[1]['label'].': '.$b->metrics[1]['value'] : null,
            'url' => route('business.show', $b->slug),
        ]);
@endphp
<section class="block block-companies">
    @foreach ($companies as $company)
        <details>
            <summary>{{ $company['name'] }}</summary>
            @if ($company['description'])<p>{{ $company['description'] }}</p>@endif
            <ul class="metrics">
                @foreach (array_filter([$company['metric_1'], $company['metric_2']]) as $metric)<li>{{ $metric }}</li>@endforeach
            </ul>
            @if ($company['url'])<a href="{{ $company['url'] }}">Find out more</a>@endif
        </details>
    @endforeach
</section>
