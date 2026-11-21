@extends('client::layout')
@section('title', 'Báo giá tin VIP')
@section('content')
@php($prefix = site_setting('transfer_prefix') ?: 'BDS')
@include('client::partials.page-head', ['title' => 'Báo giá đăng tin VIP', 'lead' => 'Phí tính theo ngày, trừ trực tiếp từ số dư ví. Tin bị từ chối được hoàn tiền 100%.', 'crumbs' => ['Báo giá tin VIP' => null]])
<section class="section">
    <div class="container">
        <div class="pricing-grid">
            @foreach($packages as $package)
                <article class="price-card" style="--pkg: {{ $package->color }}">
                    @if($package->badge)<span class="tag">{{ $package->badge }}</span>@endif
                    <h3>{{ $package->name }}</h3>
                    <div class="amount">{{ $package->price_per_day > 0 ? number_format($package->price_per_day, 0, ',', '.').' ₫' : 'Miễn phí' }} <small>/ ngày</small></div>
                    @if($package->description)<p>{{ $package->description }}</p>@endif
                    <ul>
                        <li><i class="fas fa-check"></i>Tối thiểu {{ $package->min_days }} ngày</li>
                        <li><i class="fas fa-check"></i>30 ngày: {{ cms_money($package->price_per_day * 30) }}</li>
                        @if($package->priority > 0)<li><i class="fas fa-check"></i>Ưu tiên hiển thị mức {{ $package->priority }}</li>@endif
                    </ul>
                </article>
            @endforeach
        </div>

        <h2 class="mt-24" style="margin-bottom:12px;font-size:1.3rem">Bảng giá chi tiết</h2>
        <div class="table-scroll">
            <table class="price-table">
                <thead><tr><th>Gói tin</th>@foreach(config('cms.listing_days') as $days)<th>{{ $days }} ngày</th>@endforeach</tr></thead>
                <tbody>
                @foreach($packages as $package)
                    <tr><th><span style="color:{{ $package->color }}">●</span> {{ $package->name }}</th>
                        @foreach(config('cms.listing_days') as $days)<td>{{ $days < $package->min_days ? '—' : cms_money($package->price_per_day * $days) }}</td>@endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="contact-grid mt-24" style="margin-top:28px">
            <div class="box">
                <h2>Quy trình nạp tiền vào tài khoản</h2>
                <ol class="steps">
                    <li>Đăng nhập, vào <a class="link-red" href="{{ route('client.wallet') }}">Nạp tiền</a> và tạo yêu cầu với số tiền cần nạp.</li>
                    <li>Chuyển khoản tới tài khoản bên cạnh với nội dung: <strong>{{ $prefix }} NAP[mã yêu cầu] [SĐT của bạn]</strong>.</li>
                    <li>Sau khi nhận được tiền, chúng tôi cộng vào ví để bạn đăng tin VIP (thường trong 15–30 phút giờ hành chính).</li>
                    <li>Cần hỗ trợ thêm vui lòng liên hệ hotline/zalo: <a class="link-red" href="tel:{{ preg_replace('/[^0-9+]/', '', (string) site_setting('phone')) }}">{{ site_setting('phone') }}</a></li>
                </ol>
            </div>
            <div>
                <div class="bank-card">
                    <div><span>Ngân hàng</span><strong>{{ site_setting('bank_name') }}</strong></div>
                    <div><span>Số tài khoản</span><strong>{{ site_setting('bank_account') }}</strong></div>
                    <div><span>Chủ tài khoản</span><strong>{{ site_setting('bank_owner') }}</strong></div>
                    <div><span>Chi nhánh</span><strong>{{ site_setting('bank_branch') }}</strong></div>
                </div>
                @if($rules = site_setting('posting_rules'))
                    <div class="box mt-24"><h3>Quy định đăng tin</h3><div class="rich-text" style="font-size:.9rem">{!! nl2br(e($rules)) !!}</div></div>
                @endif
                <a class="btn btn-primary btn-lg btn-block mt-24" href="{{ route('client.listings.create') }}"><i class="fas fa-pen"></i> Đăng tin ngay</a>
            </div>
        </div>
    </div>
</section>
@endsection
