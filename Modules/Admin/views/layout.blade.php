<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('assets/admin/filemanager/css/icons.css') }}">
    <link rel="stylesheet" href="{{ asset('studio/admin.css') }}?v={{ filemtime(public_path('studio/admin.css')) }}">
    <script src="{{ asset('studio/admin.js') }}?v={{ filemtime(public_path('studio/admin.js')) }}" defer></script>
    @stack('assets')
</head>
<body>
@php($adminCampaign = app(\App\Services\AdminCampaignFeed::class)->get())
<div class="shell">
    <aside id="sidebar" class="sidebar" aria-label="Menu quản trị">
        <a class="brand" href="{{ route('admin.home') }}" title="{{ config('app.name') }}">
            <span class="brand-icon"><i class="fas fa-layer-group"></i></span><strong>{{ config('app.name') }}</strong>
        </a>
        <nav>
            @if(admin_can('dashboard.view'))
                <p class="nav-label">Tổng quan</p>
                <a href="{{ route('admin.dashboard') }}" title="Dashboard" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif><i class="fas fa-th-large"></i><span>Dashboard</span></a>
            @endif
            <p class="nav-label">Nội dung</p>
            @foreach (config('cms.resources') as $key => $item)
                @continue($key === 'users' || !admin_can($key.'.view'))
                <a href="{{ route('admin.index', $key) }}" title="{{ $item['name'] }}" @if(request()->route('resource') === $key) aria-current="page" @endif><i class="fas fa-{{ $item['icon'] }}"></i><span>{{ $item['name'] }}</span></a>
            @endforeach
            @if(admin_can('media.view'))
                <a href="{{ route('admin.media', ['type' => 'image']) }}" title="Thư viện ảnh" @if(request()->routeIs('admin.media')) aria-current="page" @endif><i class="fas fa-folder-open"></i><span>Thư viện ảnh</span></a>
            @endif
            @if(admin_can('members.view') || admin_can('topups.view'))
                <p class="nav-label">Sàn rao vặt</p>
                @php($pendingTopups = admin_can('topups.view') ? \Illuminate\Support\Facades\DB::table('topup_requests')->where('status', 'pending')->count() : 0)
                @if(admin_can('members.view'))
                    <a href="{{ route('admin.members') }}" title="Thành viên" @if(request()->routeIs('admin.members*')) aria-current="page" @endif><i class="fas fa-user-friends"></i><span>Thành viên</span></a>
                @endif
                @if(admin_can('topups.view'))
                    <a href="{{ route('admin.topups') }}" title="Yêu cầu nạp tiền" @if(request()->routeIs('admin.topups')) aria-current="page" @endif><i class="fas fa-wallet"></i><span>Nạp tiền @if($pendingTopups)<b class="nav-count">{{ $pendingTopups }}</b>@endif</span></a>
                    <a href="{{ route('admin.transactions') }}" title="Lịch sử giao dịch" @if(request()->routeIs('admin.transactions')) aria-current="page" @endif><i class="fas fa-receipt"></i><span>Giao dịch ví</span></a>
                @endif
            @endif
            @if(admin_can('settings.view') || admin_can('users.view'))
                <p class="nav-label">Hệ thống</p>
            @endif
            @if(admin_can('settings.view'))
                <a href="{{ route('admin.settings') }}" title="Cấu hình website" @if(request()->routeIs('admin.settings')) aria-current="page" @endif><i class="fas fa-cog"></i><span>Cấu hình website</span></a>
            @endif
            @if(admin_can('users.view'))
                <a href="{{ route('admin.index', 'users') }}" title="Tài khoản quản trị" @if(request()->route('resource') === 'users') aria-current="page" @endif><i class="fas fa-users"></i><span>Tài khoản quản trị</span></a>
            @endif
            @if(admin_can('roles.manage'))
                <a href="{{ route('admin.permissions') }}" title="Quản lý quyền" @if(request()->routeIs('admin.permissions')) aria-current="page" @endif><i class="fas fa-shield-alt"></i><span>Quản lý quyền</span></a>
            @endif
        </nav>
        @if ($adminCampaign['image'])
            <div class="sidebar-campaign">
                <img src="{{ $adminCampaign['image'] }}" alt="Quảng cáo WebFX" referrerpolicy="no-referrer">
            </div>
        @endif
    </aside>
    <button class="scrim" data-scrim hidden type="button" aria-label="Đóng menu"></button>
    <div class="workspace">
        <header class="topbar">
            <div class="top-left">
                <button type="button" class="icon-button" data-sidebar aria-controls="sidebar" aria-expanded="true" aria-label="Thu gọn hoặc mở menu" title="Thu gọn hoặc mở menu"><i class="fas fa-bars"></i></button>
                <span class="crumb">Quản trị <i class="fas fa-chevron-right"></i> @yield('title', 'Dashboard')</span>
            </div>
            <div class="top-right">
                <a class="site-link" href="{{ route('home') }}" target="_blank" rel="noopener"><i class="fas fa-external-link-alt"></i> <span>Xem website</span></a>
                <details class="account">
                    <summary><span class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span><span class="account-name">{{ auth()->user()->name }}<small>{{ config('cms.roles.'.auth()->user()->role) }}</small></span><i class="fas fa-chevron-down"></i></summary>
                    <form method="post" action="{{ route('admin.logout') }}">@csrf<button type="submit"><i class="fas fa-sign-out-alt"></i> Đăng xuất</button></form>
                </details>
            </div>
        </header>
        <main id="main" class="main">
            @if(session('status'))<div class="notice" role="status"><i class="fas fa-check-circle"></i> {{ session('status') }}</div>@endif
            @if($errors->any())
                <div class="error" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            @yield('content')
        </main>
        @if ($adminCampaign['links'])
            <footer class="admin-campaign-footer" aria-label="Quảng cáo WebFX">
                <div class="admin-campaign-viewport" id="admin-campaign-links">
                    <div class="admin-campaign-track">
                        <ul class="admin-campaign-group">
                            @foreach ($adminCampaign['links'] as $link)
                                <li><a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer">{{ $link['text'] }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <button type="button" class="admin-campaign-pause" data-campaign-pause aria-pressed="false" aria-controls="admin-campaign-links" aria-label="Tạm dừng dòng quảng cáo">
                    <i class="fas fa-pause" aria-hidden="true"></i>
                    <i class="fas fa-play" aria-hidden="true"></i>
                </button>
            </footer>
        @endif
    </div>
</div>
@stack('dialogs')
</body>
</html>
