<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $website = app(\Modules\Client\Services\WebsiteService::class);
        $siteName = site_setting('site_name', config('app.name'));
        $menuSeo = $website->menuMetadata(request()->getPathInfo());
        $pageTitle = html_entity_decode(trim($__env->yieldContent('title')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $seoTitle = $item->seo_title ?? $menuSeo?->seo_title
            ?? (isset($item) ? $item->name.' · '.$siteName : ($pageTitle !== '' ? $pageTitle.' · '.$siteName : site_setting('seo_title', $siteName)));
        $seoDescription = $item->seo_description ?? $menuSeo?->seo_description
            ?? site_setting('seo_description', site_setting('tagline', ''));
        // Logo và copyright lấy từ WebFX: GET {LICENSE_ISSUER}/api/v1/template-brand. Logo trong Cấu hình chỉ là dự phòng.
        $brand = app(\App\Services\TemplateBrandFeed::class)->get();
        $brandLogo = $brand['logo'] ?: site_setting('logo');
        $hotline = site_setting('phone');
        $hotlineHref = 'tel:'.preg_replace('/[^0-9+]/', '', (string) $hotline);
        $zalo = preg_replace('/[^0-9]/', '', (string) site_setting('zalo'));
        $member = is_member() ? auth()->user() : null;
        $socials = array_filter([
            'facebook' => ['fab fa-facebook-f', 'Facebook'], 'youtube' => ['fab fa-youtube', 'YouTube'], 'tiktok' => ['fab fa-tiktok', 'TikTok'],
        ], fn ($meta, $key) => filled(site_setting($key)), ARRAY_FILTER_USE_BOTH);
    @endphp
    <meta name="description" content="{{ $seoDescription }}">
    <title>{{ $seoTitle }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700;900&display=swap">
    <link rel="stylesheet" href="{{ asset('assets/admin/filemanager/css/icons.css') }}">
    <link rel="stylesheet" href="{{ asset('studio/site.css') }}?v={{ filemtime(public_path('studio/site.css')) }}">
    <script src="{{ asset('studio/site.js') }}?v={{ filemtime(public_path('studio/site.js')) }}" defer></script>
    @stack('assets')
</head>
<body>
<a class="skip-link" href="#main">Bỏ qua điều hướng</a>
<header class="site-header">
    <div class="header-inner">
        <a class="brand" href="{{ route('home') }}" aria-label="{{ $siteName }} – Trang chủ">
            @if($brandLogo)<img src="{{ $brandLogo }}" alt="{{ $siteName }}">@else<span class="brand-text"><i class="fas fa-home"></i> {{ $siteName }}</span>@endif
        </a>
        <nav class="main-nav" data-nav id="main-nav" aria-label="Điều hướng chính">
            @include('client::menu', ['items' => $website->menu()])
            <div class="mobile-only mobile-actions">
                <a class="btn btn-outline" href="{{ route('client.lookup') }}"><i class="fas fa-search"></i> Tra cứu tin đăng</a>
                @if($member)
                    <a class="btn btn-outline" href="{{ route('client.account') }}"><i class="fas fa-user"></i> Tài khoản ({{ cms_money($member->balance) }})</a>
                @else
                    <a class="btn btn-outline" href="{{ route('client.login') }}">Đăng nhập</a>
                    <a class="btn btn-ghost" href="{{ route('client.register') }}">Đăng ký</a>
                @endif
            </div>
        </nav>
        <div class="header-actions">
            <a class="text-action hide-lg" href="{{ route('client.lookup') }}" title="Tra cứu trạng thái tin đăng"><i class="fas fa-search"></i> Tra cứu tin</a>
            <span class="divider hide-lg" aria-hidden="true"></span>
            @if($member)
                <details class="account-menu">
                    <summary><span class="avatar">{{ mb_strtoupper(mb_substr($member->name, 0, 1)) }}</span><span class="hide-md">{{ \Illuminate\Support\Str::limit($member->name, 18) }}</span><i class="fas fa-chevron-down" style="font-size:.7rem"></i></summary>
                    <div class="dropdown">
                        <div class="balance-chip">Số dư ví<strong>{{ cms_money($member->balance) }}</strong></div>
                        <a href="{{ route('client.account') }}"><i class="fas fa-list"></i> Quản lý tin đăng</a>
                        <a href="{{ route('client.wallet') }}"><i class="fas fa-wallet"></i> Nạp tiền & lịch sử ví</a>
                        <a href="{{ route('client.profile') }}"><i class="fas fa-user-cog"></i> Thông tin tài khoản</a>
                        <form method="post" action="{{ route('client.logout') }}">@csrf<button type="submit"><i class="fas fa-sign-out-alt"></i> Đăng xuất</button></form>
                    </div>
                </details>
            @else
                <a class="text-action hide-md" href="{{ route('client.login') }}">Đăng nhập</a>
                <span class="divider hide-md" aria-hidden="true"></span>
                <a class="text-action hide-md" href="{{ route('client.register') }}">Đăng ký</a>
            @endif
            <a class="btn btn-primary" href="{{ route('client.listings.create') }}"><i class="fas fa-pen"></i> Đăng tin</a>
        </div>
        <button class="nav-toggle" type="button" data-nav-toggle aria-controls="main-nav" aria-expanded="false" aria-label="Mở menu"><i class="fas fa-bars"></i></button>
    </div>
</header>

<main id="main">
    @if(session('status') || ($errors->any() && empty($hideErrorSummary)))
        <div class="container flash-wrap">
            @if(session('status'))<div class="alert alert-success" role="status"><i class="fas fa-check-circle"></i> <span>{{ session('status') }}</span></div>@endif
            @if($errors->any() && empty($hideErrorSummary))<div class="alert alert-error" role="alert"><i class="fas fa-exclamation-circle"></i> <span>Vui lòng kiểm tra lại các trường được đánh dấu đỏ bên dưới.</span></div>@endif
        </div>
    @endif
    @yield('content')
</main>

<footer class="site-footer">
    <div class="container footer-main">
        <div class="footer-brand">
            @if($brandLogo)<img src="{{ $brandLogo }}" alt="{{ $siteName }}" loading="lazy">@else<p class="brand-text">{{ $siteName }}</p>@endif
            <p><strong>{{ site_setting('company_name', $siteName) }}</strong></p>
            @if($about = site_setting('footer_about'))<p>{{ $about }}</p>@endif
            @if($socials)
                <div class="socials">
                    @foreach($socials as $key => [$icon, $label])<a href="{{ site_setting($key) }}" target="_blank" rel="noopener" aria-label="{{ $label }}"><i class="{{ $icon }}"></i></a>@endforeach
                    @if($zalo)<a href="https://zalo.me/{{ $zalo }}" target="_blank" rel="noopener" aria-label="Zalo">Zl</a>@endif
                </div>
            @endif
        </div>
        <div>
            <h3>Bất động sản</h3>
            <ul>
                <li><a href="{{ route('client.sale') }}">Nhà đất bán</a></li>
                <li><a href="{{ route('client.rent') }}">Nhà đất cho thuê</a></li>
                @foreach($website->categories()->take(4) as $category)
                    <li><a href="{{ route($category->type === 'rent' ? 'client.rent' : 'client.sale', ['danh-muc' => $category->key]) }}">{{ $category->name }}</a></li>
                @endforeach
            </ul>
        </div>
        <div>
            <h3>Hỗ trợ</h3>
            <ul>
                <li><a href="{{ route('client.listings.create') }}">Đăng tin bất động sản</a></li>
                <li><a href="{{ route('client.pricing') }}">Báo giá tin VIP</a></li>
                <li><a href="{{ route('client.lookup') }}">Tra cứu tin đăng</a></li>
                <li><a href="{{ route('client.posts') }}">Tin tức thị trường</a></li>
                <li><a href="{{ route('client.contact.page') }}">Liên hệ</a></li>
            </ul>
        </div>
        <div>
            <h3>Liên hệ</h3>
            <ul class="footer-contact">
                @if($address = site_setting('address'))<li><i class="fas fa-map-marker-alt"></i><span>{{ $address }}</span></li>@endif
                @if($hotline)<li><i class="fas fa-phone-alt"></i><a href="{{ $hotlineHref }}">Hotline/Zalo: {{ $hotline }}</a></li>@endif
                @if($email = site_setting('email'))<li><i class="fas fa-envelope"></i><a href="mailto:{{ $email }}">{{ $email }}</a></li>@endif
                @if($hours = site_setting('working_hours'))<li><i class="far fa-clock"></i><span>{{ $hours }}</span></li>@endif
            </ul>
            <form class="subscribe-form" method="post" action="{{ route('client.subscribe') }}" novalidate style="margin-top:14px">
                @csrf
                <label class="sr-only" for="subscribe-email">Email nhận tin</label>
                <input id="subscribe-email" name="email" type="email" placeholder="Email nhận tin BĐS mới" value="{{ $errors->subscribe->any() ? old('email') : '' }}" @if($errors->subscribe->has('email')) aria-invalid="true" aria-describedby="subscribe-error" class="field-invalid" @endif>
                <button class="btn btn-primary" type="submit" aria-label="Đăng ký nhận tin"><i class="fas fa-paper-plane"></i></button>
            </form>
            @if($errors->subscribe->has('email'))<p id="subscribe-error" class="field-error" role="alert" style="margin-top:6px">{{ $errors->subscribe->first('email') }}</p>@endif
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container">
            @if($brand['copyright'])
                <div>{!! $brand['copyright'] !!}</div>
            @else
                <p>© {{ now()->year }} {{ $siteName }}. All rights reserved.</p>
            @endif
            <p><a href="{{ route('client.lookup') }}"><i class="fas fa-search"></i> Tra cứu tin đăng</a> · <a href="{{ route('client.pricing') }}">Báo giá tin VIP</a></p>
        </div>
    </div>
</footer>

<div class="floating">
    @if($zalo)<a class="zalo" href="https://zalo.me/{{ $zalo }}" target="_blank" rel="noopener" aria-label="Chat Zalo">Zalo</a>@endif
    @if($hotline)<a class="call" href="{{ $hotlineHref }}" aria-label="Gọi hotline {{ $hotline }}"><i class="fas fa-phone-alt"></i></a>@endif
    <button class="top" type="button" data-back-top hidden aria-label="Về đầu trang"><i class="fas fa-arrow-up"></i></button>
</div>
</body>
</html>
