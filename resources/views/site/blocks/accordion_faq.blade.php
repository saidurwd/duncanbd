<section class="block block-faq">
    @if ($data['heading'])<h2>{{ $data['heading'] }}</h2>@endif
    @foreach ($data['items'] ?? [] as $qa)
        <details><summary>{{ $qa['question'] }}</summary><div class="prose">{!! $qa['answer'] !!}</div></details>
    @endforeach
</section>
