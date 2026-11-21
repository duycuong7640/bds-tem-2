@php
    $photos = count(json_decode($listing->gallery ?? '[]', true) ?: []);
    $isVip = !empty($listing->package_badge) && $listing->priority > 0;
@endphp
<a class="listing-card @if($isVip) is-vip @endif" href="{{ route('client.product', $listing->slug) }}">
    <div class="thumb">
        <img src="{{ $listing->image }}" alt="{{ $listing->name }}" loading="lazy">
        @if($isVip)<span class="vip-badge" style="background: {{ $listing->package_color }}">{{ $listing->package_badge }}</span>@endif
        @if($photos > 1)<span class="photo-count"><i class="far fa-image"></i> {{ $photos }}</span>@endif
    </div>
    <div class="body">
        <h3>{{ $listing->name }}</h3>
        <div class="meta"><span>{{ bds_price($listing->price, $listing->listing_type) }}</span><span>{{ bds_area($listing->area) }}</span></div>
        <p class="location"><i class="fas fa-map-marker-alt"></i> <span>{{ collect([$listing->district, $listing->province])->filter()->implode(' - ') }}</span></p>
        @if($listing->bedrooms || $listing->toilets)
            <div class="specs">
                @if($listing->bedrooms)<span title="Phòng ngủ"><i class="fas fa-bed"></i> {{ $listing->bedrooms }}</span>@endif
                @if($listing->toilets)<span title="Toilet"><i class="fas fa-bath"></i> {{ $listing->toilets }}</span>@endif
            </div>
        @endif
        <div class="foot"><span>{{ $listing->published_at ? \Illuminate\Support\Carbon::parse($listing->published_at)->diffForHumans() : '' }}</span><span>Mã: {{ $listing->id }}</span></div>
    </div>
</a>
