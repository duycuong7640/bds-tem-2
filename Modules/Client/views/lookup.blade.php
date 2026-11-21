@extends('client::layout')
@section('title', 'Tra cứu tin đăng')
@section('content')
@php
    $bag = $errors->lookup;
    $invalid = fn (string $field) => $bag->has($field) ? 'aria-invalid=true aria-describedby=lookup-'.$field.'-error' : '';
@endphp
@include('client::partials.page-head', ['title' => 'Tra cứu tin đăng', 'lead' => 'Kiểm tra trạng thái duyệt, gói tin và hạn hiển thị bằng mã tin và số điện thoại liên hệ.', 'crumbs' => ['Tra cứu tin đăng' => null]])
<section class="section">
    <div class="container">
        <form class="form-card lookup-card" method="post" action="{{ route('client.lookup.search') }}" novalidate>
            @csrf
            <h2><i class="fas fa-search"></i> Thông tin tra cứu</h2>
            <div class="form-grid" style="grid-template-columns:1fr">
                <div class="field"><label for="lookup-code">Mã tin <span class="req">*</span></label><input id="lookup-code" name="code" value="{{ old('code', $result->id ?? '') }}" maxlength="20" placeholder="VD: 1024" inputmode="numeric" {{ $invalid('code') }}>@include('client::field-error', ['field' => 'code', 'bag' => 'lookup'])</div>
                <div class="field"><label for="lookup-phone">Số điện thoại liên hệ trên tin <span class="req">*</span></label><input id="lookup-phone" name="phone" type="tel" value="{{ old('phone', $result->contact_phone ?? '') }}" maxlength="20" placeholder="VD: 0912345678" {{ $invalid('phone') }}>@include('client::field-error', ['field' => 'phone', 'bag' => 'lookup'])</div>
            </div>
            <div class="form-actions"><button class="btn btn-primary btn-block btn-lg" type="submit"><i class="fas fa-search"></i> Tra cứu</button></div>
            <p class="hint" style="margin-top:10px"><i class="fas fa-lock"></i> Thông tin chỉ hiển thị khi mã tin và số điện thoại khớp nhau.</p>
        </form>
        @if($result)
            @php
                $expired = $result->status === 'approved' && $result->expires_at && $result->expires_at <= now()->toDateTimeString();
                $state = $expired ? 'expired' : $result->status;
                $label = $expired ? 'Đã hết hạn' : (['pending' => 'Đang chờ duyệt', 'approved' => 'Đang hiển thị', 'rejected' => 'Bị từ chối'][$result->status] ?? $result->status);
            @endphp
            <div class="form-card result-card">
                <h2><i class="fas fa-clipboard-check"></i> Kết quả: tin #{{ $result->id }}</h2>
                <span class="status {{ $state }}">{{ $label }}</span>
                <dl>
                    <dt>Tiêu đề</dt><dd>{{ $result->name }}</dd>
                    <dt>Gói tin</dt><dd>{{ $result->package_name ?? 'Tin thường' }} @if($result->days) · {{ $result->days }} ngày @endif</dd>
                    <dt>Ngày đăng</dt><dd>{{ \Illuminate\Support\Carbon::parse($result->created_at)->format('d/m/Y H:i') }}</dd>
                    <dt>Hết hạn</dt><dd>{{ $result->expires_at ? \Illuminate\Support\Carbon::parse($result->expires_at)->format('d/m/Y H:i') : ($result->status === 'pending' ? 'Tính từ khi được duyệt' : 'Không giới hạn') }}</dd>
                    @if($result->status === 'rejected' && $result->reject_reason)<dt>Lý do từ chối</dt><dd class="reject-note">{{ $result->reject_reason }}</dd>@endif
                </dl>
                <div class="form-actions">
                    @if($state === 'approved' && $result->active)<a class="btn btn-primary" href="{{ route('client.product', $result->slug) }}">Xem tin trên website</a>@endif
                    <a class="btn btn-outline" href="{{ route('client.account') }}">Quản lý tin của tôi</a>
                </div>
            </div>
        @endif
    </div>
</section>
@endsection
