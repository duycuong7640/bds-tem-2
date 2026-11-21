@extends('client::layout')
@section('title', 'Quản lý tin đăng')
@section('content')
<div class="container account-layout">
    @include('client::account.nav')
    <div>
        <div class="section-head"><div><h2>Quản lý tin đăng</h2><p>Theo dõi trạng thái duyệt, hạn hiển thị và chỉnh sửa tin.</p></div>
            <a class="btn btn-primary" href="{{ route('client.listings.create') }}"><i class="fas fa-plus"></i> Đăng tin mới</a></div>
        <nav class="status-tabs" aria-label="Lọc tin">
            @foreach(['' => ['Tất cả', 'all'], 'approved' => ['Đang hiển thị', 'approved'], 'pending' => ['Chờ duyệt', 'pending'], 'rejected' => ['Bị từ chối', 'rejected'], 'expired' => ['Hết hạn', 'expired']] as $key => [$label, $countKey])
                <a href="{{ route('client.account', array_filter(['trang-thai' => $key])) }}" @if((string) $status === $key) aria-current="page" @endif>{{ $label }} ({{ $counts[$countKey] }})</a>
            @endforeach
        </nav>
        @forelse($records as $listing)
            @php
                $expired = $listing->status === 'approved' && $listing->expires_at && $listing->expires_at <= now()->toDateTimeString();
                $state = $expired ? 'expired' : $listing->status;
                $labels = ['pending' => 'Chờ duyệt', 'approved' => 'Đang hiển thị', 'rejected' => 'Bị từ chối', 'expired' => 'Hết hạn'];
            @endphp
            <article class="my-listing">
                <img src="{{ $listing->image }}" alt="" loading="lazy">
                <div class="info">
                    <h3>{{ $listing->name }}</h3>
                    <div><span class="status {{ $state }}">{{ $labels[$state] }}</span> &nbsp; Mã tin: <strong>{{ $listing->id }}</strong>
                        @if($listing->package_name) · <span style="color:{{ $listing->package_color }};font-weight:600">{{ $listing->package_name }}</span>@endif</div>
                    <div><strong style="color:var(--red)">{{ bds_price($listing->price, $listing->listing_type) }}</strong> · {{ bds_area($listing->area) }} · {{ $listing->province }}</div>
                    <div class="hint">
                        Đăng: {{ \Illuminate\Support\Carbon::parse($listing->created_at)->format('d/m/Y') }}
                        @if($listing->expires_at) · Hết hạn: {{ \Illuminate\Support\Carbon::parse($listing->expires_at)->format('d/m/Y H:i') }}@endif
                        @if($listing->fee) · Phí: {{ cms_money($listing->fee) }}@endif
                        · {{ number_format($listing->views, 0, ',', '.') }} lượt xem
                    </div>
                    @if($state === 'rejected' && $listing->reject_reason)<div class="reject-note"><i class="fas fa-info-circle"></i> Lý do: {{ $listing->reject_reason }} – phí đã được hoàn vào ví. Sửa tin để đăng lại.</div>@endif
                    @if($state === 'pending')<div class="hint"><i class="fas fa-hourglass-half"></i> Tin đang được kiểm duyệt. Hạn đăng tính từ lúc duyệt.</div>@endif
                </div>
                <div class="actions">
                    @if($state === 'approved' && $listing->active)<a class="btn btn-outline btn-sm" href="{{ route('client.product', $listing->slug) }}" target="_blank"><i class="far fa-eye"></i> Xem</a>@endif
                    <a class="btn btn-outline btn-sm" href="{{ route('client.listings.edit', $listing->id) }}"><i class="fas fa-pen"></i> {{ in_array($state, ['rejected', 'expired'], true) ? 'Đăng lại' : 'Sửa' }}</a>
                    <form method="post" action="{{ route('client.listings.destroy', $listing->id) }}" data-confirm="Xóa tin #{{ $listing->id }}? {{ $state === 'pending' ? 'Phí đang giữ sẽ được hoàn vào ví.' : 'Thao tác không thể hoàn tác.' }}">
                        @csrf @method('DELETE')
                        <button class="btn btn-outline btn-sm btn-block" type="submit" style="color:var(--red)"><i class="far fa-trash-alt"></i> Xóa</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="empty-state"><i class="far fa-folder-open"></i><p>Bạn chưa có tin đăng nào trong mục này.</p>
                <a class="btn btn-primary mt-24" href="{{ route('client.listings.create') }}">Đăng tin đầu tiên</a></div>
        @endforelse
        {{ $records->onEachSide(1)->links('client::pagination') }}
    </div>
</div>
@endsection
