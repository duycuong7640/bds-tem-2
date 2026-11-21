@php
    $admin = request()->is('admin', 'admin/*');
    $appName = config('app.name');
    [$title, $description] = match ($status) {
        400 => ['Yêu cầu không hợp lệ', 'Kiểm tra lại đường dẫn hoặc mở trang khác để tiếp tục.'],
        401 => ['Cần đăng nhập', 'Đăng nhập để truy cập trang này.'],
        403 => ['Bạn không có quyền truy cập', $admin ? 'Liên hệ quản trị viên nếu bạn cần quyền vào mục này.' : 'Trang này đang giới hạn quyền truy cập.'],
        404 => ['Không tìm thấy trang', 'Đường dẫn có thể đã thay đổi hoặc trang đã được gỡ.'],
        405 => ['Thao tác không được hỗ trợ', 'Mở trang từ menu để tiếp tục.'],
        419 => ['Phiên làm việc đã hết hạn', 'Mở lại trang rồi thử thực hiện thao tác một lần nữa.'],
        422 => ['Dữ liệu chưa hợp lệ', 'Kiểm tra thông tin đã nhập và thử lại.'],
        429 => ['Bạn đã gửi quá nhiều yêu cầu', 'Chờ một lúc rồi thử lại.'],
        500 => ['Hệ thống gặp sự cố', 'Vui lòng thử lại sau ít phút.'],
        502, 504 => ['Kết nối tạm thời gián đoạn', 'Vui lòng thử lại sau ít phút.'],
        503 => ($licenseUnavailable ?? false)
            ? ['Website chưa có giấy phép hợp lệ', 'Vui lòng liên hệ đơn vị triển khai website.']
            : ['Hệ thống tạm thời gián đoạn', 'Vui lòng quay lại sau ít phút.'],
        default => ['Không thể mở trang', 'Vui lòng thử lại hoặc quay về trang chính.'],
    };
    $brandUrl = url($admin ? '/admin' : '/');
@endphp
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $status }} · {{ $title }} · {{ $appName }}</title>
    <link rel="stylesheet" href="{{ asset('studio/errors.css') }}">
</head>
<body class="error-page {{ $admin ? 'error-page-admin' : 'error-page-client' }}">
    <header class="error-header">
        <a class="error-brand" href="{{ $brandUrl }}">{{ $appName }} @if($admin)<span>Quản trị</span>@endif</a>
        @if($admin)<a class="error-top-link" href="{{ url('/') }}">Về website</a>@endif
    </header>
    <main class="error-main" aria-labelledby="error-title">
        <div class="error-card">
            <p class="error-code" aria-label="Mã lỗi {{ $status }}">{{ $status }}</p>
            <h1 id="error-title">{{ $title }}</h1>
            <p class="error-description">{{ $description }}</p>
            <div class="error-actions">
                @if($admin && in_array($status, [401, 419], true))
                    <a class="error-button" href="{{ url('/admin/login') }}">Đăng nhập quản trị</a>
                @else
                    <a class="error-button" href="{{ $brandUrl }}">{{ $admin ? 'Về quản trị' : 'Về trang chủ' }}</a>
                @endif
            </div>
        </div>
    </main>
</body>
</html>
