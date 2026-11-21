<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">
    <title>Đăng nhập · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/admin/filemanager/css/icons.css') }}"><link rel="stylesheet" href="{{ asset('studio/admin.css') }}">
</head>
<body class="login-page">
<main class="login-card">
    <a class="brand login-brand" href="{{ route('home') }}"><span class="brand-icon"><i class="fas fa-layer-group"></i></span><strong>{{ config('app.name') }}</strong></a>
    <h1>Đăng nhập quản trị</h1>
    <form novalidate method="post" action="{{ route('admin.login.store') }}">
        @csrf
        <label>Email<input id="field-email" name="email" @error('email') aria-invalid="true" aria-describedby="error-email" @enderror type="email" value="{{ old('email') }}" autocomplete="username" required autofocus></label>
        @include('admin::field-error', ['field' => 'email'])
        <label>Mật khẩu<input id="field-password" name="password" @error('password') aria-invalid="true" aria-describedby="error-password" @enderror type="password" autocomplete="current-password" required></label>
        @include('admin::field-error', ['field' => 'password'])
        <label class="checkbox"><input type="checkbox" name="remember" value="1"> Ghi nhớ đăng nhập</label>
        <button class="button primary" type="submit">Đăng nhập <i class="fas fa-arrow-right"></i></button>
    </form>
    <a class="text-link" href="{{ route('home') }}">← Về website</a>
</main>
</body>
</html>
