@php($user = auth()->user())
<aside class="account-nav">
    <div class="profile">
        <span class="avatar">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
        <div><strong>{{ $user->name }}</strong><small>Mã TV: {{ $user->id }} · {{ $user->phone }}</small></div>
    </div>
    <div class="wallet">
        <span>Số dư tài khoản</span>
        <strong>{{ cms_money($user->balance) }}</strong>
        <a href="{{ route('client.wallet') }}"><i class="fas fa-plus"></i> Nạp tiền</a>
    </div>
    <nav aria-label="Tài khoản">
        <a href="{{ route('client.listings.create') }}" @if(request()->routeIs('client.listings.create')) aria-current="page" @endif><i class="fas fa-pen"></i> Đăng tin mới</a>
        <a href="{{ route('client.account') }}" @if(request()->routeIs('client.account', 'client.listings.edit')) aria-current="page" @endif><i class="fas fa-list"></i> Quản lý tin đăng</a>
        <a href="{{ route('client.wallet') }}" @if(request()->routeIs('client.wallet')) aria-current="page" @endif><i class="fas fa-wallet"></i> Nạp tiền & lịch sử ví</a>
        <a href="{{ route('client.profile') }}" @if(request()->routeIs('client.profile')) aria-current="page" @endif><i class="fas fa-user-cog"></i> Thông tin tài khoản</a>
        <a href="{{ route('client.pricing') }}"><i class="fas fa-crown"></i> Bảng giá tin VIP</a>
        <form method="post" action="{{ route('client.logout') }}">@csrf<button type="submit"><i class="fas fa-sign-out-alt"></i> Đăng xuất</button></form>
    </nav>
</aside>
