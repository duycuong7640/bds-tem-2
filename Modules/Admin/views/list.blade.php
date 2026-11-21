@extends('admin::layout')
@php
    $item = config('cms.resources.'.$resource);
    $canCreate = admin_can($resource.'.edit') && !in_array($resource, ['contacts', 'subscribers'], true);
@endphp
@section('title', $item['name'])
@section('content')
<div class="intro">
    <div><h1>{{ $item['name'] }}</h1><p>{{ $resource === 'menus' ? 'Sắp xếp điều hướng theo cấu trúc cha – con.' : 'Quản lý dữ liệu và trạng thái hiển thị.' }}</p></div>
    @if($canCreate)<a class="button primary" href="{{ route('admin.create', $resource) }}"><i class="fas fa-plus"></i> Thêm mới</a>@endif
</div>
<div class="panel table-panel">
    @if($resource === 'products')
        @php($currentStatus = request('status'))
        <nav class="filter-tabs" aria-label="Lọc theo trạng thái duyệt">
            <a href="{{ route('admin.index', ['products', 'q' => $query ?: null]) }}" @if(!$currentStatus) aria-current="page" @endif>Tất cả <b>{{ array_sum($statusCounts) }}</b></a>
            @foreach(config('cms.listing_statuses') as $key => $label)
                <a href="{{ route('admin.index', ['products', 'status' => $key, 'q' => $query ?: null]) }}" @if($currentStatus === $key) aria-current="page" @endif>{{ $label }} <b>{{ $statusCounts[$key] ?? 0 }}</b></a>
            @endforeach
        </nav>
    @endif
    @if($resource !== 'menus')
        <form class="list-tools" method="get" action="{{ route('admin.index', $resource) }}">
            @if($resource === 'products' && request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
            <label class="search"><i class="fas fa-search"></i><input name="q" value="{{ $query }}" maxlength="120" placeholder="{{ $resource === 'subscribers' ? 'Tìm theo email' : ($resource === 'products' ? 'Tiêu đề, mã tin hoặc SĐT' : 'Tìm theo tên') }}" aria-label="Tìm kiếm"></label>
            <button class="button">Tìm</button>
            @if($query !== '')<a href="{{ route('admin.index', $resource) }}" class="text-link">Xóa lọc</a>@endif
            <span class="result-count">{{ $records->total() }} mục</span>
        </form>
    @endif
    <div class="table-scroll">
        <table>
            <thead><tr>
                <th>{{ $resource === 'subscribers' ? 'Email' : 'Tên' }}</th>
                @if($resource === 'posts')<th>Danh mục</th><th>Ngày xuất bản</th>@endif
                @if($resource === 'products')<th>Mã tin</th><th>Giá</th><th>Danh mục</th><th>Gói / Hết hạn</th><th>Duyệt</th>@endif
                @if($resource === 'packages')<th>Giá / ngày</th><th>Ưu tiên</th><th>Tối thiểu</th>@endif
                @if($resource === 'menus')<th>Thứ tự</th><th>Loại danh mục</th>@endif
                @if($resource === 'banners')<th>Vị trí</th>@endif
                @if($resource === 'users')<th>Vai trò</th>@endif
                <th>Trạng thái</th><th>Cập nhật</th><th><span class="sr-only">Thao tác</span></th>
            </tr></thead>
            <tbody>
            @forelse($records as $record)
                <tr>
                    <td>
                        <div class="record-name" @isset($record->depth) style="padding-inline-start: {{ min(8, $record->depth) * 18 }}px" @endisset>
                            @if(!empty($record->image))<img class="thumb" src="{{ $record->image }}" alt="">@endif
                            @if(!empty($record->depth))<i class="fas fa-level-up-alt tree-icon"></i>@endif
                            <div><a href="{{ route('admin.form', [$resource, $record->id]) }}">{{ $record->name ?? $record->email }}</a>
                            @if(isset($record->name) && ($subtitle = $record->email ?? $record->slug ?? $record->url ?? null))<small>{{ $subtitle }}</small>@endif</div>
                        </div>
                    </td>
                    @if($resource === 'posts')
                        <td>{{ $categories[$record->menu_id]->name ?? 'Chưa phân loại' }}</td>
                        <td>{{ $record->published_at ? \Illuminate\Support\Carbon::parse($record->published_at)->format('d/m/Y H:i') : '—' }}</td>
                    @endif
                    @if($resource === 'products')
                        <td>#{{ $record->id }}<small class="muted-line">{{ config('cms.listing_types.'.$record->listing_type) }}{{ $record->user_id ? ' · Thành viên' : ' · Admin' }}</small></td>
                        <td>{{ bds_price($record->price, $record->listing_type) }}</td>
                        <td>{{ $categories[$record->menu_id]->name ?? 'Chưa phân loại' }}</td>
                        <td>{{ $packages[$record->package_id] ?? '—' }}<small class="muted-line">{{ $record->expires_at ? 'HH: '.\Illuminate\Support\Carbon::parse($record->expires_at)->format('d/m/Y') : ($record->status === 'pending' && $record->days ? 'Tính khi duyệt' : 'Không hết hạn') }}</small></td>
                        <td>
                            <span class="badge {{ ['pending' => 'orange', 'approved' => 'green', 'rejected' => 'red'][$record->status] ?? '' }}">{{ config('cms.listing_statuses.'.$record->status) }}</span>
                            @if($record->status === 'pending' && admin_can('products.edit'))
                                <div class="actions" style="margin-top:6px">
                                    <form method="post" action="{{ route('admin.listings.approve', $record->id) }}">@csrf<button class="icon-button" title="Duyệt tin" aria-label="Duyệt tin"><i class="fas fa-check"></i></button></form>
                                    <a class="icon-button danger" href="{{ route('admin.form', ['products', $record->id]) }}#field-status" title="Từ chối (nhập lý do)" aria-label="Từ chối"><i class="fas fa-ban"></i></a>
                                </div>
                            @endif
                        </td>
                    @endif
                    @if($resource === 'packages')
                        <td>{{ cms_money($record->price_per_day) }}</td><td>{{ $record->priority }}</td><td>{{ $record->min_days }} ngày</td>
                    @endif
                    @if($resource === 'menus')<td>{{ $record->position }}</td><td>{{ match($record->content_type) {'posts' => 'Bài viết', 'products' => 'Sản phẩm', default => 'Điều hướng'} }}</td>@endif
                    @if($resource === 'banners')<td>{{ $record->placement === 'slide' ? 'Slide' : 'Quảng cáo' }}</td>@endif
                    @if($resource === 'users')<td>{{ config('cms.roles.'.$record->role) }}</td>@endif
                    <td>
                        @if($resource === 'contacts')
                            <span class="badge {{ $record->status === 'new' ? 'green' : '' }}">{{ ['new' => 'Mới', 'read' => 'Đã đọc', 'resolved' => 'Đã xử lý'][$record->status] }}</span>
                        @else
                            <span class="badge {{ $record->active ? 'green' : '' }}">{{ $record->active ? (in_array($resource, ['products', 'posts']) ? ($record->published_at > now()->toDateTimeString() ? 'Đã lên lịch' : 'Đã xuất bản') : 'Đang hoạt động') : (in_array($resource, ['products', 'posts']) ? 'Bản nháp' : 'Đã tắt') }}</span>
                        @endif
                    </td>
                    <td>{{ \Illuminate\Support\Carbon::parse($record->updated_at)->format('d/m/Y') }}</td>
                    <td>
                        <div class="actions">
                            <a class="icon-button" href="{{ route('admin.form', [$resource, $record->id]) }}" title="Mở chi tiết" aria-label="Mở chi tiết"><i class="fas fa-{{ admin_can($resource.'.edit') ? 'pen' : 'eye' }}"></i></a>
                            @if(admin_can($resource.'.delete') && !($resource === 'users' && $record->id === auth()->id()))
                                <form method="post" action="{{ route('admin.delete', [$resource, $record->id]) }}" data-confirm="Xóa mục này?">
                                    @csrf @method('DELETE')
                                    <button class="icon-button danger" title="Xóa" aria-label="Xóa"><i class="far fa-trash-alt"></i></button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="10"><p class="empty">Chưa có dữ liệu phù hợp.</p></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $records->onEachSide(1)->links('admin::pagination') }}
</div>
@endsection
