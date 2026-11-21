@extends('admin::layout')
@section('title', 'Yêu cầu nạp tiền')
@section('content')
<div class="intro"><div><h1>Yêu cầu nạp tiền</h1><p>Đối chiếu sao kê ngân hàng theo nội dung chuyển khoản rồi duyệt để cộng tiền vào ví thành viên.</p></div></div>
<div class="panel table-panel">
    <nav class="filter-tabs" aria-label="Lọc trạng thái">
        <a href="{{ route('admin.topups') }}" @if(!$status) aria-current="page" @endif>Tất cả <b>{{ array_sum($counts) }}</b></a>
        @foreach(['pending' => 'Chờ duyệt', 'approved' => 'Đã cộng tiền', 'rejected' => 'Từ chối'] as $key => $label)
            <a href="{{ route('admin.topups', ['status' => $key]) }}" @if($status === $key) aria-current="page" @endif>{{ $label }} <b>{{ $counts[$key] ?? 0 }}</b></a>
        @endforeach
    </nav>
    @error('topup')<div class="error" role="alert" style="margin:14px 18px 0">{{ $message }}</div>@enderror
    <div class="table-scroll" style="margin-top:12px"><table>
        <thead><tr><th>Mã</th><th>Thành viên</th><th>Số tiền</th><th>Nội dung CK</th><th>Thời gian</th><th>Trạng thái</th><th>Xử lý</th></tr></thead>
        <tbody>
        @forelse($records as $topup)
            @php($bag = $errors->getBag('topup'.$topup->id))
            <tr>
                <td>#{{ $topup->id }}</td>
                <td><a href="{{ route('admin.members.edit', $topup->user_id) }}">{{ $topup->member_name }}</a><small class="muted-line">{{ $topup->member_phone }} · {{ $topup->member_email }}</small></td>
                <td><strong>{{ cms_money($topup->amount) }}</strong></td>
                <td><code>{{ $topup->transfer_note }}</code></td>
                <td>{{ \Illuminate\Support\Carbon::parse($topup->created_at)->format('d/m/Y H:i') }}</td>
                <td>
                    <span class="badge {{ ['pending' => 'orange', 'approved' => 'green', 'rejected' => 'red'][$topup->status] }}">{{ ['pending' => 'Chờ duyệt', 'approved' => 'Đã cộng tiền', 'rejected' => 'Từ chối'][$topup->status] }}</span>
                    @if($topup->admin_note)<small class="muted-line">{{ $topup->admin_note }}</small>@endif
                </td>
                <td>
                    @if($topup->status === 'pending' && admin_can('topups.edit'))
                        <div class="inline-form">
                            <form method="post" action="{{ route('admin.topups.approve', $topup->id) }}" data-confirm="Xác nhận đã nhận {{ cms_money($topup->amount) }} và cộng vào ví?">
                                @csrf<button class="button primary" type="submit"><i class="fas fa-check"></i> Duyệt</button>
                            </form>
                            <form method="post" action="{{ route('admin.topups.reject', $topup->id) }}" class="inline-form" novalidate>
                                @csrf
                                <input name="admin_note" placeholder="Lý do từ chối" maxlength="255" value="{{ $bag->any() ? old('admin_note') : '' }}" aria-label="Lý do từ chối" @if($bag->has('admin_note')) aria-invalid="true" @endif>
                                <button class="button" type="submit">Từ chối</button>
                                @if($bag->has('admin_note'))<p class="field-error" role="alert">{{ $bag->first('admin_note') }}</p>@endif
                            </form>
                        </div>
                    @else
                        <small class="muted-line">{{ $topup->processed_at ? \Illuminate\Support\Carbon::parse($topup->processed_at)->format('d/m/Y H:i') : '—' }}</small>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7"><p class="empty">Chưa có yêu cầu nạp tiền.</p></td></tr>
        @endforelse
        </tbody>
    </table></div>
    {{ $records->onEachSide(1)->links('admin::pagination') }}
</div>
@endsection
