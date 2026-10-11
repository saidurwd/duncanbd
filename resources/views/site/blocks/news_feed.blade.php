@inject('newsQuery', 'App\Domain\News\NewsQuery')
<section class="block block-news">
    @if ($data['heading'])<h2>{{ $data['heading'] }}</h2>@endif
    <div class="cards">
        @foreach ($newsQuery->latest((int) ($data['limit'] ?: 3)) as $post)@include('site.news._card', ['post' => $post])@endforeach
    </div>
    <a href="{{ route('news.index') }}">All news</a>
</section>
