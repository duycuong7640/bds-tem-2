<a class="post-card" href="{{ route('client.post', $post->slug) }}">
    <div class="thumb"><img src="{{ $post->image }}" alt="{{ $post->name }}" loading="lazy"></div>
    <h3>{{ $post->name }}</h3>
    @if($showExcerpt ?? false)
        <span class="date"><i class="far fa-calendar-alt"></i> {{ $post->published_at ? \Illuminate\Support\Carbon::parse($post->published_at)->format('d/m/Y') : '' }}</span>
        <p class="excerpt">{{ \Illuminate\Support\Str::limit(strip_tags(html_entity_decode((string) $post->description)), 160) }}</p>
    @endif
</a>
