@extends('client::layout')
@section('content')
@php
    $slide = $banners->get('slide')?->first();
    $locations = [
        ['Hồ Chí Minh', 'Tp Hồ Chí Minh', '/storage/media/library/bds/du-an-1.jpg'],
        ['Hà Nội', 'Hà Nội', '/storage/media/library/bds/du-an-3.jpg'],
        ['Đà Nẵng', 'Đà Nẵng', '/storage/media/library/bds/du-an-5.jpg'],
        ['Bình Dương', 'Bình Dương', '/storage/media/library/bds/du-an-7.jpg'],
        ['Khánh Hòa', 'Khánh Hòa', '/storage/media/library/bds/du-an-9.jpg'],
    ];
@endphp

<section class="hero" @if($slide) style="background-image:url('{{ $slide->image }}')" @endif>
    <div class="container hero-inner">
        @include('client::partials.search-box')
        <h1>{{ $slide->name ?? site_setting('tagline', 'Kênh mua bán, cho thuê nhà đất uy tín') }}</h1>
        <p class="lead">Hơn {{ number_format(max($totalListings, 1), 0, ',', '.') }} tin đăng nhà đất đang hiển thị. Đăng tin nhanh – tiếp cận đúng người mua.
            <a href="{{ route('client.lookup') }}" style="text-decoration:underline">Tra cứu trạng thái tin đã đăng</a></p>
    </div>
</section>

@if($featured->isNotEmpty())
<section class="section">
    <div class="container">
        <div class="section-head"><div><h2>Nhà đất nổi bật</h2><p>Tin VIP được ưu tiên hiển thị</p></div><a class="link-red" href="{{ route('client.search') }}">Xem tất cả <i class="fas fa-angle-right"></i></a></div>
        <div class="grid-4">
            @foreach($featured as $listing)@include('client::partials.listing-card')@endforeach
        </div>
    </div>
</section>
@endif

<section class="section section-white">
    <div class="container">
        <div class="section-head"><h2>Bất động sản theo địa điểm</h2></div>
        <div class="locations">
            @foreach($locations as [$province, $label, $image])
                <a class="location-tile" href="{{ route('client.search', ['tinh' => $province]) }}" style="background-image:url('{{ $image }}')">
                    <span>Nhà đất</span><strong>{{ $label }}</strong>
                    <small>{{ number_format($provinceCounts[$province] ?? 0, 0, ',', '.') }} tin đăng</small>
                </a>
            @endforeach
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head"><h2>Nhà đất bán mới nhất</h2><a class="link-red" href="{{ route('client.sale', ['sap-xep' => 'moi-nhat']) }}">Xem tất cả <i class="fas fa-angle-right"></i></a></div>
        @if($latestSale->isNotEmpty())
            <div class="grid-4">@foreach($latestSale as $listing)@include('client::partials.listing-card')@endforeach</div>
        @else
            <div class="empty-state"><i class="fas fa-home"></i><p>Chưa có tin bán.</p></div>
        @endif
    </div>
</section>

@if($ad = $banners->get('advertisement')?->first())
<section class="container">
    @if($ad->url)<a class="ad-banner" href="{{ $ad->url }}"><img src="{{ $ad->image }}" alt="{{ $ad->name }}" loading="lazy"></a>
    @else<div class="ad-banner"><img src="{{ $ad->image }}" alt="{{ $ad->name }}" loading="lazy"></div>@endif
</section>
@endif

<section class="section">
    <div class="container">
        <div class="section-head"><h2>Nhà đất cho thuê mới nhất</h2><a class="link-red" href="{{ route('client.rent', ['sap-xep' => 'moi-nhat']) }}">Xem tất cả <i class="fas fa-angle-right"></i></a></div>
        @if($latestRent->isNotEmpty())
            <div class="grid-4">@foreach($latestRent as $listing)@include('client::partials.listing-card')@endforeach</div>
        @else
            <div class="empty-state"><i class="fas fa-key"></i><p>Chưa có tin cho thuê.</p></div>
        @endif
    </div>
</section>

<section class="section section-white">
    <div class="container">
        <div class="cta-band">
            <div>
                <h2>Bạn có nhà đất cần bán hoặc cho thuê?</h2>
                <p>Đăng ký tài khoản, nạp tiền vào ví và đăng tin chỉ trong 3 phút. Tin VIP luôn nằm ở vị trí nổi bật trên trang chủ và đầu kết quả tìm kiếm.</p>
                <div class="form-actions">
                    <a class="btn btn-primary btn-lg" href="{{ route('client.listings.create') }}"><i class="fas fa-pen"></i> Đăng tin ngay</a>
                    <a class="btn btn-outline btn-lg" href="{{ route('client.pricing') }}">Xem bảng giá tin VIP</a>
                </div>
            </div>
            <div class="cta-steps">
                <ul>
                    <li><b>1</b> Đăng ký tài khoản thành viên miễn phí</li>
                    <li><b>2</b> Nạp tiền vào ví qua chuyển khoản ngân hàng</li>
                    <li><b>3</b> Chọn gói tin, số ngày và đăng tin</li>
                    <li><b>4</b> Tin được kiểm duyệt và hiển thị trên website</li>
                </ul>
            </div>
        </div>
    </div>
</section>

@if($posts->isNotEmpty())
<section class="section">
    <div class="container">
        <div class="section-head"><h2>Tin tức nổi bật</h2><a class="link-red" href="{{ route('client.posts') }}">Xem tất cả <i class="fas fa-angle-right"></i></a></div>
        <div class="grid-4">@foreach($posts as $post)@include('client::partials.post-card')@endforeach</div>
    </div>
</section>
@endif

<section class="section section-white">
    <div class="container">
        <ul class="province-links">
            @foreach(config('cms.provinces') as $province)
                <li><a href="{{ route('client.search', ['tinh' => $province]) }}">Nhà đất {{ $province }}</a></li>
            @endforeach
        </ul>
    </div>
</section>
@endsection
