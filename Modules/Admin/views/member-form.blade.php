@extends('admin::layout')
@section('title', 'Thành viên')
@section('content')
@php
    $canEdit = admin_can('members.edit');
    $adjust = $errors->adjust;
@endphp
<div class="intro">
    <div><a class="text-link back" href="{{ route('admin.members') }}">← Thành viên</a><h1>{{ $member->name }}</h1></div>
</div>
<div class="member-summary">
    <div><span>Số dư ví</span><strong>{{ cms_money($member->balance) }}</strong></div>
    <div><span>Đã chi phí đăng tin</span><strong>{{ cms_money($spent) }}</strong></div>
    <div><span>Số tin đã đăng</span><strong>{{ $listings->count() }}</strong></div>
    <div><span>Tham gia</span><strong>{{ \Illuminate\Support\Carbon::parse($member->created_at)->format('d/m/Y') }}</strong></div>
</div>
<div class="editorial-layout">
    <form novalidate class="panel edit-panel" method="post" action="{{ route('admin.members.update', $member->id) }}">
        @csrf @method('PUT')
        <h2>Thông tin tài khoản</h2>
        <fieldset class="form-grid" @disabled(!$canEdit)>
            @foreach(['name' => 'Họ tên', 'email' => 'Email', 'phone' => 'Số điện thoại'] as $field => $label)
                <div class="field">
                    <label for="field-{{ $field }}">{{ $label }} <span class="field-required" aria-hidden="true">*</span></label>
                    <input id="field-{{ $field }}" name="{{ $field }}" type="{{ $field === 'email' ? 'email' : ($field === 'phone' ? 'tel' : 'text') }}" value="{{ old($field, $member->{$field}) }}" @error($field) aria-invalid="true" aria-describedby="error-{{ $field }}" @enderror>
                    @include('admin::field-error')
                </div>
            @endforeach
            <div class="field">
                <label for="field-active">Trạng thái</label>
                <select id="field-active" name="active">
                    <option value="1" @selected((int) old('active', $member->active) === 1)>Hoạt động</option>
                    <option value="0" @selected((int) old('active', $member->active) === 0)>Khóa tài khoản</option>
                </select>
                @include('admin::field-error', ['field' => 'active'])
            </div>
            <div class="field wide">
                <label for="field-password">Đặt lại mật khẩu</label>
                <input id="field-password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" @error('password') aria-invalid="true" aria-describedby="error-password" @enderror>
                <small>Để trống để giữ mật khẩu hiện tại.</small>
                @include('admin::field-error', ['field' => 'password'])
            </div>
        </fieldset>
        @if($canEdit)<div class="form-actions"><button class="button primary"><i class="fas fa-check"></i> Lưu thay đổi</button></div>@endif
    </form>
    <aside class="panel edit-panel">
        <h2>Điều chỉnh số dư</h2>
        @if($canEdit)
        <form novalidate method="post" action="{{ route('admin.members.adjust', $member->id) }}" class="form-grid">
            @csrf
            <div class="field wide">
                <label for="adjust-direction">Loại</label>
                <select id="adjust-direction" name="direction">
                    <option value="add" @selected(old('direction') !== 'subtract')>Cộng tiền</option>
                    <option value="subtract" @selected(old('direction') === 'subtract')>Trừ tiền</option>
                </select>
            </div>
            <div class="field wide">
                <label for="adjust-amount">Số tiền (₫) <span class="field-required" aria-hidden="true">*</span></label>
                <input id="adjust-amount" name="amount" type="number" min="1000" step="1000" value="{{ old('amount') }}" @if($adjust->has('amount')) aria-invalid="true" aria-describedby="error-adjust-amount" @endif>
                @if($adjust->has('amount'))<p id="error-adjust-amount" class="field-error" role="alert">{{ $adjust->first('amount') }}</p>@endif
            </div>
            <div class="field wide">
                <label for="adjust-note">Lý do <span class="field-required" aria-hidden="true">*</span></label>
                <input id="adjust-note" name="note" maxlength="200" value="{{ old('note') }}" placeholder="VD: Khuyến mãi tháng 11" @if($adjust->has('note')) aria-invalid="true" aria-describedby="error-adjust-note" @endif>
                @if($adjust->has('note'))<p id="error-adjust-note" class="field-error" role="alert">{{ $adjust->first('note') }}</p>@endif
            </div>
            <div class="field wide"><button class="button primary"><i class="fas fa-coins"></i> Cập nhật số dư</button></div>
        </form>
        @else
            <p class="empty">Bạn không có quyền điều chỉnh số dư.</p>
        @endif
    </aside>
</div>

<div class="panel table-panel" style="margin-top:16px">
    <div class="panel-head" style="padding:18px"><div><h2>Tin đăng gần đây</h2></div></div>
    <div class="table-scroll"><table>
        <thead><tr><th>Tin</th><th>Giá</th><th>Duyệt</th><th>Phí</th><th>Ngày đăng</th></tr></thead>
        <tbody>
        @forelse($listings as $listing)
            <tr>
                <td><a href="{{ route('admin.form', ['products', $listing->id]) }}">#{{ $listing->id }} · {{ $listing->name }}</a></td>
                <td>{{ bds_price($listing->price, $listing->listing_type) }}</td>
                <td><span class="badge {{ ['pending' => 'orange', 'approved' => 'green', 'rejected' => 'red'][$listing->status] ?? '' }}">{{ config('cms.listing_statuses.'.$listing->status) }}</span></td>
                <td>{{ cms_money($listing->fee) }}</td>
                <td>{{ \Illuminate\Support\Carbon::parse($listing->created_at)->format('d/m/Y') }}</td>
            </tr>
        @empty
            <tr><td colspan="5"><p class="empty">Chưa có tin đăng.</p></td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>

<div class="panel table-panel" style="margin-top:16px">
    <div class="panel-head" style="padding:18px"><div><h2>Giao dịch ví gần đây</h2></div>@if(admin_can('topups.view'))<a class="text-link" href="{{ route('admin.transactions', ['q' => $member->email]) }}">Xem tất cả →</a>@endif</div>
    @include('admin::transaction-rows', ['records' => $transactions, 'showMember' => false])
</div>
@endsection
