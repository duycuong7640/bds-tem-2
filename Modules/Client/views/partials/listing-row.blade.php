@php
    $photos = count(json_decode($listing->gallery ?? '[]', true) ?: []);
    $isVip = !empty($listing->package_badge) && $listing->priority > 0;
@endphp
<article class="listing-row @if($isVip) is-vip @endif">
    <a class="thumb" href="{{ route('client.product', $listing->slug) }}" tabindex="-1" aria-hidden="true">
        <img src="{{ $listing->image }}" alt="" loading="lazy">
        @if($isVip)<span class="vip-badge" style="background: {{ $listing->package_color }}">{{ $listing->package_badge }}</span>@endif
        @if($photos > 1)<span class="photo-count"><i class="far fa-image"></i> {{ $photos }}</span>@endif
    </a>
    <div class="body">
        <h3><a href="{{ route('client.product', $listing->slug) }}">{{ $listing->name }}</a></h3>
        <div class="meta">
            <span>{{ bds_price($listing->price, $listing->listing_type) }}</span>
            <span>{{ bds_area($listing->area) }}</span>
            @if($listing->price > 0 && $listing->area > 0 && $listing->listing_type === 'sale')<span class="unit">~{{ bds_price((int) round($listing->price / $listing->area)) }}/m²</span>@endif
        </div>
        <p class="row-location"><i class="fas fa-map-marker-alt"></i> {{ collect([$listing->district, $listing->province])->filter()->implode(', ') }}</p>
        <p class="excerpt">{{ \Illuminate\Support\Str::limit(strip_tags(html_entity_decode((string) $listing->description)), 200) }}</p>
        <div class="foot">
            <span>
                @if($listing->bedrooms)<i class="fas fa-bed"></i> {{ $listing->bedrooms }} PN &nbsp;@endif
                @if($listing->toilets)<i class="fas fa-bath"></i> {{ $listing->toilets }} WC &nbsp;@endif
                <i class="far fa-clock"></i> {{ $listing->published_at ? \Illuminate\Support\Carbon::parse($listing->published_at)->format('d/m/Y') : '' }}
            </span>
            <a class="btn btn-ghost btn-sm" href="{{ route('client.product', $listing->slug) }}">Xem chi tiết</a>
        </div>
    </div>
</article>
