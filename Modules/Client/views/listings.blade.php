@extends('client::layout')
@section('title', $title)
@section('content')
@php
    $website = app(\Modules\Client\Services\WebsiteService::class);
    $type = $filters['type'];
    $baseRoute = match ($type) { 'sale' => 'client.sale', 'rent' => 'client.rent', default => 'client.search' };
    $query = request()->except('page');
@endphp
@include('client::partials.page-head', [
    'title' => $title,
    'lead' => 'Hiện có '.number_format($listings->total(), 0, ',', '.').' bất động sản phù hợp.',
    'crumbs' => array_filter([
        ($type === 'rent' ? 'Nhà đất cho thuê' : ($type === 'sale' ? 'Nhà đất bán' : 'Tìm kiếm')) => $category ? route($baseRoute) : null,
        ($category->name ?? null) => null,
    ], fn ($value, $key) => $key !== '', ARRAY_FILTER_USE_BOTH),
])
<section class="section">
    <div class="container with-sidebar">
        <div>
            <div class="results-bar">
                <span>Tìm thấy <strong>{{ number_format($listings->total(), 0, ',', '.') }}</strong> tin đăng</span>
                <form method="get" action="{{ route($baseRoute) }}">
                    @foreach($query as $key => $value)@continue($key === 'sap-xep' || is_array($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endforeach
                    <label class="sr-only" for="sort">Sắp xếp</label>
                    <select id="sort" name="sap-xep" data-autosubmit>
                        @foreach(['noi-bat' => 'Thông thường (VIP trước)', 'moi-nhat' => 'Tin mới nhất', 'gia-tang' => 'Giá thấp đến cao', 'gia-giam' => 'Giá cao đến thấp', 'dien-tich' => 'Diện tích lớn nhất'] as $value => $label)
                            <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            @forelse($listings as $listing)
                @include('client::partials.listing-row')
            @empty
                <div class="empty-state"><i class="fas fa-search-location"></i><p>Không có tin đăng phù hợp. Hãy thử bỏ bớt điều kiện lọc.</p>
                    <a class="btn btn-outline mt-24" href="{{ route($baseRoute) }}">Xóa bộ lọc</a></div>
            @endforelse
            {{ $listings->onEachSide(1)->links('client::pagination') }}
        </div>
        <aside>
            <form class="box filter-box" method="get" action="{{ route($baseRoute) }}">
                <h2><i class="fas fa-filter" style="color:var(--red)"></i> Lọc tin đăng</h2>
                @if(!$type)
                    <div class="field">
                        <label for="f-type">Hình thức</label>
                        <select id="f-type" name="loai"><option value="">Tất cả</option>@foreach(config('cms.listing_types') as $value => $label)<option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>@endforeach</select>
                    </div>
                @endif
                <div class="field">
                    <label for="f-q">Từ khóa</label>
                    <input id="f-q" name="q" value="{{ $filters['q'] }}" maxlength="120" placeholder="Đường, quận, dự án...">
                </div>
                <div class="field">
                    <label for="f-cat">Loại nhà đất</label>
                    <select id="f-cat" name="danh-muc">
                        <option value="">Tất cả</option>
                        @foreach($website->categories($type) as $cat)<option value="{{ $cat->key }}" @selected($filters['category'] === $cat->key)>{{ $cat->name }}</option>@endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="f-province">Tỉnh/Thành phố</label>
                    <select id="f-province" name="tinh"><option value="">Toàn quốc</option>@foreach(config('cms.provinces') as $province)<option @selected($filters['province'] === $province)>{{ $province }}</option>@endforeach</select>
                </div>
                @if($priceRanges)
                    <div class="field">
                        <label for="f-price">Mức giá</label>
                        <select id="f-price" name="gia"><option value="">Tất cả mức giá</option>@foreach($priceRanges as $value => $label)<option value="{{ $value }}" @selected($filters['price'] === $value)>{{ $label }}</option>@endforeach</select>
                    </div>
                @endif
                <div class="field">
                    <label for="f-area">Diện tích</label>
                    <select id="f-area" name="dien-tich"><option value="">Tất cả diện tích</option>@foreach(config('cms.area_ranges') as $value => $label)<option value="{{ $value }}" @selected($filters['area'] === $value)>{{ $label }}</option>@endforeach</select>
                </div>
                <div class="form-actions"><button class="btn btn-primary btn-block" type="submit"><i class="fas fa-search"></i> Áp dụng</button></div>
                <p class="center" style="margin-top:8px"><a class="link-red" href="{{ route($baseRoute) }}">Xóa bộ lọc</a></p>
            </form>
            @if($type)
            <div class="box">
                <h3>{{ $type === 'rent' ? 'Cho thuê theo loại' : 'Bán theo loại' }}</h3>
                <ul class="side-links">
                    @foreach($website->categories($type) as $cat)
                        <li><a href="{{ route($baseRoute, ['danh-muc' => $cat->key]) }}">{{ $cat->name }} <i class="fas fa-angle-right"></i></a></li>
                    @endforeach
                </ul>
            </div>
            @endif
            <div class="box" style="background:var(--red-soft)">
                <h3>Bạn muốn đăng tin?</h3>
                <p style="font-size:.9rem;color:var(--text)">Tin VIP hiển thị đầu trang và nổi bật trên trang chủ.</p>
                <a class="btn btn-primary btn-block mt-24" href="{{ route('client.listings.create') }}"><i class="fas fa-pen"></i> Đăng tin ngay</a>
            </div>
        </aside>
    </div>
</section>
@endsection
