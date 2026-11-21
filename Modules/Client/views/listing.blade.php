@extends('client::layout')
@section('content')
@php
    $typeRoute = $item->listing_type === 'rent' ? 'client.rent' : 'client.sale';
    $location = collect([$item->address, $item->district, $item->province])->filter()->implode(', ');
    $contactName = $item->contact_name ?: ($owner->name ?? site_setting('company_name'));
    $maskedPhone = $item->contact_phone ? substr($item->contact_phone, 0, -3).' ***' : null;
@endphp
@include('client::partials.page-head', [
    'title' => $item->name,
    'crumbs' => array_filter([
        ($item->listing_type === 'rent' ? 'Nhà đất cho thuê' : 'Nhà đất bán') => route($typeRoute),
        ($category->name ?? '') => $category ? route($typeRoute, ['danh-muc' => $category->key]) : null,
        ($item->province ?? '') => route($typeRoute, ['tinh' => $item->province]),
    ], fn ($value, $key) => $key !== '', ARRAY_FILTER_USE_BOTH),
])
<section class="section">
    <div class="container with-sidebar">
        <article>
            <div data-gallery>
                <div class="gallery-main">
                    <img data-gallery-main src="{{ $gallery[0] ?? $item->image }}" alt="{{ $item->name }}">
                    @if(count($gallery) > 1)
                        <button class="gallery-nav prev" type="button" data-gallery-prev aria-label="Ảnh trước"><i class="fas fa-chevron-left"></i></button>
                        <button class="gallery-nav next" type="button" data-gallery-next aria-label="Ảnh sau"><i class="fas fa-chevron-right"></i></button>
                        <span class="gallery-counter" data-gallery-counter>1/{{ count($gallery) }}</span>
                    @endif
                    @if(!empty($item->package_badge) && $item->priority > 0)<span class="vip-badge" style="background: {{ $item->package_color }}">{{ $item->package_badge }}</span>@endif
                </div>
                @if(count($gallery) > 1)
                    <div class="gallery-thumbs">
                        @foreach($gallery as $index => $photo)
                            <button type="button" data-gallery-thumb data-src="{{ $photo }}" aria-current="{{ $index === 0 ? 'true' : 'false' }}" aria-label="Xem ảnh {{ $index + 1 }}"><img src="{{ $photo }}" alt="" loading="lazy"></button>
                        @endforeach
                    </div>
                @endif
            </div>

            <p class="detail-location"><i class="fas fa-map-marker-alt"></i> {{ $location }}</p>
            <h2 class="detail-title">{{ $item->name }}</h2>
            <div class="key-facts">
                <div><span>Mức giá</span><strong>{{ bds_price($item->price, $item->listing_type) }}</strong></div>
                <div><span>Diện tích</span><strong>{{ bds_area($item->area) }}</strong></div>
                @if($item->bedrooms)<div><span>Phòng ngủ</span><strong class="plain">{{ $item->bedrooms }} PN</strong></div>@endif
                <div><span>Mã tin</span><strong class="plain">{{ $item->id }}</strong></div>
                <div><span>Ngày đăng</span><strong class="plain">{{ $item->published_at ? \Illuminate\Support\Carbon::parse($item->published_at)->format('d/m/Y') : '—' }}</strong></div>
            </div>

            <section class="detail-section">
                <h2>Thông tin mô tả</h2>
                <div class="rich-text">{!! $item->body !!}</div>
            </section>

            <div class="detail-section spec-wrap" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:28px">
                <section>
                    <h2>Đặc điểm bất động sản</h2>
                    <div class="spec-table" style="grid-template-columns:1fr">
                        <div><span>Loại tin</span><strong>{{ config('cms.listing_types.'.$item->listing_type) }} – {{ $category->name ?? 'Nhà đất' }}</strong></div>
                        <div><span>Diện tích</span><strong>{{ bds_area($item->area) }}</strong></div>
                        @if($item->floors)<div><span>Số tầng</span><strong>{{ $item->floors }} tầng</strong></div>@endif
                        @if($item->bedrooms)<div><span>Số phòng ngủ</span><strong>{{ $item->bedrooms }} phòng</strong></div>@endif
                        @if($item->toilets)<div><span>Số toilet</span><strong>{{ $item->toilets }} phòng</strong></div>@endif
                        @if($item->direction)<div><span>Hướng nhà</span><strong>{{ $item->direction }}</strong></div>@endif
                        @if($item->legal)<div><span>Pháp lý</span><strong>{{ $item->legal }}</strong></div>@endif
                    </div>
                </section>
                <section>
                    <h2>Thông tin liên hệ</h2>
                    <div class="spec-table" style="grid-template-columns:1fr">
                        <div><span>Tên liên hệ</span><strong>{{ $contactName }}</strong></div>
                        @if($item->contact_phone)<div><span>Điện thoại</span><strong>{{ $item->contact_phone }}</strong></div>@endif
                        @if($item->contact_email)<div><span>Email</span><strong>{{ $item->contact_email }}</strong></div>@endif
                        <div><span>Lượt xem</span><strong>{{ number_format($item->views + 1, 0, ',', '.') }}</strong></div>
                    </div>
                </section>
            </div>

            <section class="detail-section">
                <h2>Xem trên bản đồ</h2>
                <iframe class="map-frame" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Bản đồ {{ $location }}"
                        src="https://www.google.com/maps?q={{ urlencode($location) }}&output=embed"></iframe>
            </section>
            <p class="notice-line"><i class="fas fa-shield-alt"></i> Không đặt cọc, chuyển tiền khi chưa xem nhà và kiểm tra giấy tờ pháp lý.</p>
        </article>

        <aside class="sticky">
            <div class="box contact-card">
                <h3 style="font-family:inherit;font-size:1rem;color:var(--muted);font-weight:500">Liên hệ người đăng</h3>
                <div class="avatar-lg"><i class="fas fa-user"></i></div>
                <h3>{{ $contactName }}</h3>
                <p>Đã sẵn sàng tư vấn cho bạn</p>
                @if($item->contact_phone)
                    <a class="btn btn-green btn-block btn-lg" href="#" data-phone="{{ $item->contact_phone }}"><i class="fas fa-phone-alt"></i> <span class="phone-reveal" data-phone-text>{{ $maskedPhone }} · Hiện số</span></a>
                    <a class="btn btn-outline btn-block" href="https://zalo.me/{{ preg_replace('/\D/', '', $item->contact_phone) }}" target="_blank" rel="noopener"><i class="far fa-comment-dots"></i> Chat qua Zalo</a>
                @endif
                @if($item->contact_email)<a class="btn btn-outline btn-block" href="mailto:{{ $item->contact_email }}?subject={{ rawurlencode('Hỏi về tin #'.$item->id) }}"><i class="far fa-envelope"></i> Gửi email</a>@endif
                @if($owner && $ownerCount > 1)<p style="margin:12px 0 0">Thành viên có {{ $ownerCount }} tin đang hiển thị</p>@endif
            </div>
            <div class="box">
                <h3>Cần hỗ trợ?</h3>
                <p style="font-size:.9rem;color:var(--text)">Liên hệ hotline {{ site_setting('phone') }} để được tư vấn pháp lý & thủ tục miễn phí.</p>
                <a class="btn btn-ghost btn-block mt-24" href="{{ route('client.contact.page') }}">Gửi yêu cầu tư vấn</a>
            </div>
        </aside>
    </div>
</section>

@if($related->isNotEmpty())
<section class="section section-white">
    <div class="container">
        <div class="section-head"><h2>Bất động sản lân cận</h2></div>
        <div class="grid-4">@foreach($related as $listing)@include('client::partials.listing-card')@endforeach</div>
    </div>
</section>
@endif
@endsection
