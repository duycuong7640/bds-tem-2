@extends('client::layout', ['hideErrorSummary' => true])
@section('title', 'Đăng ký')
@section('content')
@php
    $invalid = fn (string $field) => $errors->has($field) ? 'aria-invalid=true aria-describedby=f-'.$field.'-error' : '';
    $bonus = (int) site_setting('signup_bonus', 0);
@endphp
<div class="container">
    <div class="auth-wrap">
        <div class="auth-side" style="background-image:url('/storage/media/library/bds/du-an-4.jpg')">
            <h2>Đăng ký thành viên</h2>
            <ul>
                <li><i class="fas fa-check-circle"></i> Đăng tin bán, cho thuê nhà đất toàn quốc</li>
                <li><i class="fas fa-check-circle"></i> Gói VIP ưu tiên hiển thị, tính phí theo ngày</li>
                <li><i class="fas fa-check-circle"></i> Hoàn tiền 100% nếu tin bị từ chối</li>
                @if($bonus)<li><i class="fas fa-gift"></i> Tặng {{ cms_money($bonus) }} vào ví khi đăng ký</li>@endif
            </ul>
        </div>
        <form class="auth-form" method="post" action="{{ route('client.register.store') }}" novalidate>
            @csrf
            <h1>Tạo tài khoản</h1>
            <div class="field">
                <label for="name">Họ và tên <span class="req">*</span></label>
                <input id="name" name="name" value="{{ old('name') }}" autocomplete="name" maxlength="120" {{ $invalid('name') }}>
                @include('client::field-error', ['field' => 'name'])
            </div>
            <div class="form-grid">
                <div class="field">
                    <label for="phone">Số điện thoại <span class="req">*</span></label>
                    <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel" maxlength="20" {{ $invalid('phone') }}>
                    @include('client::field-error', ['field' => 'phone'])
                </div>
                <div class="field">
                    <label for="email">Email <span class="req">*</span></label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" maxlength="254" {{ $invalid('email') }}>
                    @include('client::field-error', ['field' => 'email'])
                </div>
                <div class="field">
                    <label for="password">Mật khẩu <span class="req">*</span></label>
                    <div class="password-field">
                        <input id="password" name="password" type="password" autocomplete="new-password" maxlength="72" {{ $invalid('password') }}>
                        <button type="button" data-toggle-password aria-label="Hiện mật khẩu"><i class="far fa-eye"></i></button>
                    </div>
                    @include('client::field-error', ['field' => 'password'])
                </div>
                <div class="field">
                    <label for="password_confirmation">Nhập lại mật khẩu <span class="req">*</span></label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" maxlength="72" @if($errors->has('password')) aria-invalid="true" @endif>
                </div>
            </div>
            <small class="hint">Mật khẩu tối thiểu 8 ký tự, gồm chữ và số.</small>
            <div class="field">
                <label class="check"><input type="checkbox" name="agree" value="1" @checked(old('agree')) {{ $invalid('agree') }}> Tôi đồng ý với quy định đăng tin và điều khoản sử dụng của {{ site_setting('site_name') }}.</label>
                @include('client::field-error', ['field' => 'agree'])
            </div>
            <button class="btn btn-primary btn-lg btn-block" type="submit">Đăng ký</button>
            <p class="switch">Đã có tài khoản? <a class="link-red" href="{{ route('client.login') }}">Đăng nhập</a></p>
        </form>
    </div>
</div>
@endsection
