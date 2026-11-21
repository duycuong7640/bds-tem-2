@extends('client::layout')
@section('title', 'Liên hệ')
@section('content')
@php
    $bag = $errors->contact;
    $invalid = fn (string $field) => $bag->has($field) ? 'aria-invalid=true aria-describedby=contact-'.$field.'-error' : '';
@endphp
@include('client::partials.page-head', ['title' => 'Liên hệ với chúng tôi', 'lead' => 'Tư vấn mua bán, ký gửi và hỗ trợ đăng tin bất động sản.', 'crumbs' => ['Liên hệ' => null]])
<section class="section">
    <div class="container contact-grid">
        <div class="box">
            <h2>{{ site_setting('company_name', site_setting('site_name')) }}</h2>
            <ul class="info-list" style="margin-top:14px">
                @if($v = site_setting('address'))<li><i class="fas fa-map-marker-alt"></i><div><span>Địa chỉ văn phòng</span>{{ $v }}</div></li>@endif
                @if($v = site_setting('phone'))<li><i class="fas fa-phone-alt"></i><div><span>Hotline / Zalo</span><a href="tel:{{ preg_replace('/[^0-9+]/', '', $v) }}">{{ $v }}</a></div></li>@endif
                @if($v = site_setting('email'))<li><i class="fas fa-envelope"></i><div><span>Email</span><a href="mailto:{{ $v }}">{{ $v }}</a></div></li>@endif
                @if($v = site_setting('working_hours'))<li><i class="far fa-clock"></i><div><span>Giờ làm việc</span>{{ $v }}</div></li>@endif
                @if($v = site_setting('tax_code'))<li><i class="fas fa-file-invoice"></i><div><span>Mã số thuế</span>{{ $v }}</div></li>@endif
            </ul>
            @if($map = site_setting('map_embed'))
                <iframe class="map-frame mt-24" src="{{ $map }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Bản đồ văn phòng" allowfullscreen></iframe>
            @endif
        </div>
        <form class="form-card" method="post" action="{{ route('client.contact') }}" novalidate>
            @csrf
            <h2><i class="far fa-paper-plane"></i> Gửi yêu cầu tư vấn</h2>
            <div class="form-grid">
                <div class="field"><label for="contact-name">Họ tên <span class="req">*</span></label><input id="contact-name" name="name" value="{{ old('name') }}" maxlength="180" {{ $invalid('name') }}>@include('client::field-error', ['field' => 'name', 'bag' => 'contact'])</div>
                <div class="field"><label for="contact-phone">Số điện thoại <span class="req">*</span></label><input id="contact-phone" name="phone" type="tel" value="{{ old('phone') }}" maxlength="20" {{ $invalid('phone') }}>@include('client::field-error', ['field' => 'phone', 'bag' => 'contact'])</div>
                <div class="field wide"><label for="contact-email">Email <span class="req">*</span></label><input id="contact-email" name="email" type="email" value="{{ old('email') }}" maxlength="254" {{ $invalid('email') }}>@include('client::field-error', ['field' => 'email', 'bag' => 'contact'])</div>
                <div class="field wide"><label for="contact-message">Nội dung <span class="req">*</span></label><textarea id="contact-message" name="message" rows="6" maxlength="5000" placeholder="Bạn cần tư vấn mua, bán hay cho thuê bất động sản nào?" {{ $invalid('message') }}>{{ old('message') }}</textarea>@include('client::field-error', ['field' => 'message', 'bag' => 'contact'])</div>
            </div>
            <div class="form-actions"><button class="btn btn-primary btn-lg" type="submit"><i class="fas fa-paper-plane"></i> Gửi liên hệ</button></div>
        </form>
    </div>
</section>
@endsection
