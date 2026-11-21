@extends('client::layout')
@section('title', 'Thông tin tài khoản')
@section('content')
@php
    $user = auth()->user();
    $profile = $errors->profile;
    $password = $errors->password;
@endphp
<div class="container account-layout">
    @include('client::account.nav')
    <div>
        <form class="form-card" method="post" action="{{ route('client.profile.update') }}" novalidate>
            @csrf @method('PUT')
            <h2><i class="far fa-user"></i> Thông tin cá nhân</h2>
            <div class="form-grid">
                <div class="field">
                    <label for="name">Họ và tên <span class="req">*</span></label>
                    <input id="name" name="name" value="{{ $profile->any() ? old('name') : $user->name }}" maxlength="120" @if($profile->has('name')) aria-invalid="true" aria-describedby="profile-name-error" @endif>
                    @include('client::field-error', ['field' => 'name', 'bag' => 'profile'])
                </div>
                <div class="field">
                    <label for="phone">Số điện thoại <span class="req">*</span></label>
                    <input id="phone" name="phone" type="tel" value="{{ $profile->any() ? old('phone') : $user->phone }}" maxlength="20" @if($profile->has('phone')) aria-invalid="true" aria-describedby="profile-phone-error" @endif>
                    @include('client::field-error', ['field' => 'phone', 'bag' => 'profile'])
                </div>
                <div class="field wide">
                    <label for="email">Email đăng nhập</label>
                    <input id="email" value="{{ $user->email }}" disabled>
                    <small>Liên hệ hotline nếu cần đổi email.</small>
                </div>
            </div>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Lưu thông tin</button></div>
        </form>
        <form class="form-card" method="post" action="{{ route('client.password.update') }}" novalidate>
            @csrf @method('PUT')
            <h2><i class="fas fa-lock"></i> Đổi mật khẩu</h2>
            <div class="form-grid cols-3">
                <div class="field">
                    <label for="current_password">Mật khẩu hiện tại <span class="req">*</span></label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password" @if($password->has('current_password')) aria-invalid="true" aria-describedby="password-current_password-error" @endif>
                    @include('client::field-error', ['field' => 'current_password', 'bag' => 'password'])
                </div>
                <div class="field">
                    <label for="new_password">Mật khẩu mới <span class="req">*</span></label>
                    <input id="new_password" name="password" type="password" autocomplete="new-password" @if($password->has('password')) aria-invalid="true" aria-describedby="password-password-error" @endif>
                    @include('client::field-error', ['field' => 'password', 'bag' => 'password'])
                </div>
                <div class="field">
                    <label for="password_confirmation">Nhập lại mật khẩu mới <span class="req">*</span></label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
                </div>
            </div>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Đổi mật khẩu</button></div>
        </form>
    </div>
</div>
@endsection
