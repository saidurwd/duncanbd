<section class="block block-companies">
    @foreach ($data['items'] ?? [] as $company)
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
