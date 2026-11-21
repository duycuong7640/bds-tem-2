@extends('admin::layout')
@section('title', 'Thành viên')
@section('content')
<div class="intro">
    <div><h1>Thành viên</h1><p>Tài khoản đăng ký ngoài website để đăng tin rao bất động sản.</p></div>
</div>
<div class="panel table-panel">
    <form class="list-tools" method="get" action="{{ route('admin.members') }}">
        <label class="search"><i class="fas fa-search"></i><input name="q" value="{{ $query }}" maxlength="120" placeholder="Tên, email hoặc số điện thoại" aria-label="Tìm thành viên"></label>
        <button class="button">Tìm</button>
        @if($query !== '')<a href="{{ route('admin.members') }}" class="text-link">Xóa lọc</a>@endif
        <span class="result-count">{{ $records->total() }} thành viên</span>
    </form>
    <div class="table-scroll">
        <table>
            <thead><tr><th>Thành viên</th><th>Điện thoại</th><th>Số dư ví</th><th>Tin đăng</th><th>Trạng thái</th><th>Ngày tham gia</th><th><span class="sr-only">Thao tác</span></th></tr></thead>
            <tbody>
            @forelse($records as $member)
                <tr>
                    <td><div class="record-name"><div><a href="{{ route('admin.members.edit', $member->id) }}">{{ $member->name }}</a><small>{{ $member->email }}</small></div></div></td>
                    <td>{{ $member->phone ?: '—' }}</td>
                    <td><strong>{{ cms_money($member->balance) }}</strong></td>
                    <td>{{ $member->listing_count }}</td>
                    <td><span class="badge {{ $member->active ? 'green' : 'red' }}">{{ $member->active ? 'Hoạt động' : 'Đã khóa' }}</span></td>
                    <td>{{ \Illuminate\Support\Carbon::parse($member->created_at)->format('d/m/Y') }}</td>
                    <td><div class="actions"><a class="icon-button" href="{{ route('admin.members.edit', $member->id) }}" title="Chi tiết" aria-label="Chi tiết"><i class="fas fa-{{ admin_can('members.edit') ? 'pen' : 'eye' }}"></i></a></div></td>
                </tr>
            @empty
                <tr><td colspan="7"><p class="empty">Chưa có thành viên phù hợp.</p></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $records->onEachSide(1)->links('admin::pagination') }}
</div>
@endsection
