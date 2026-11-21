@extends('client::layout', ['hideErrorSummary' => true])
@section('title', 'Đăng nhập')
@section('content')
@php($invalid = fn (string $field) => $errors->has($field) ? 'aria-invalid=true aria-describedby=f-'.$field.'-error' : '')
<div class="container">
    <div class="auth-wrap">
        <div class="auth-side" style="background-image:url('/storage/media/library/bds/du-an-2.jpg')">
            <h2>Chào mừng trở lại!</h2>
            <ul>
                <li><i class="fas fa-check-circle"></i> Quản lý tin đăng & gia hạn nhanh</li>
                <li><i class="fas fa-check-circle"></i> Nạp tiền, theo dõi lịch sử ví</li>
                <li><i class="fas fa-check-circle"></i> Tin VIP hiển thị nổi bật</li>
            </ul>
        </div>
        <form class="auth-form" method="post" action="{{ route('client.login.store') }}" novalidate>
            @csrf
            <h1>Đăng nhập</h1>
            <div class="field">
                <label for="login">Email hoặc số điện thoại <span class="req">*</span></label>
                <input id="login" name="login" value="{{ old('login') }}" autocomplete="username" maxlength="254" {{ $invalid('login') }}>
                @include('client::field-error', ['field' => 'login'])
            </div>
            <div class="field">
                <label for="password">Mật khẩu <span class="req">*</span></label>
                <div class="password-field">
                    <input id="password" name="password" type="password" autocomplete="current-password" maxlength="72" {{ $invalid('password') }}>
                    <button type="button" data-toggle-password aria-label="Hiện mật khẩu"><i class="far fa-eye"></i></button>
                </div>
                @include('client::field-error', ['field' => 'password'])
            </div>
            <label class="check"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> Ghi nhớ đăng nhập</label>
            <button class="btn btn-primary btn-lg btn-block" type="submit">Đăng nhập</button>
            <p class="switch">Chưa có tài khoản? <a class="link-red" href="{{ route('client.register') }}">Đăng ký ngay</a></p>
            <p class="switch hint">Quên mật khẩu? Liên hệ hotline {{ site_setting('phone') }} để được cấp lại.</p>
        </form>
    </div>
</div>
@endsection
