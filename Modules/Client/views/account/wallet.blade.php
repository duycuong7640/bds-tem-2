@extends('client::layout')
@section('title', 'Nạp tiền & lịch sử ví')
@section('content')
@php
    $bag = $errors->topup;
    $user = auth()->user();
    $min = (int) site_setting('min_topup', 50000);
@endphp
<div class="container account-layout">
    @include('client::account.nav')
    <div>
        <div class="section-head"><div><h2>Nạp tiền vào ví</h2><p>Chuyển khoản ngân hàng, admin xác nhận và cộng tiền vào ví để đăng tin.</p></div></div>
        <div class="contact-grid">
            <form class="form-card" method="post" action="{{ route('client.topup') }}" novalidate>
                @csrf
                <h2><i class="fas fa-coins"></i> Tạo yêu cầu nạp tiền</h2>
                <div class="field">
                    <label for="amount">Số tiền cần nạp (₫) <span class="req">*</span></label>
                    <input id="amount" name="amount" type="number" min="{{ $min }}" step="1000" value="{{ old('amount') }}" placeholder="Tối thiểu {{ number_format($min, 0, ',', '.') }}" @if($bag->has('amount')) aria-invalid="true" aria-describedby="topup-amount-error" @endif>
                    @include('client::field-error', ['field' => 'amount', 'bag' => 'topup'])
                </div>
                <div class="amount-presets" data-amount-presets="#amount" style="margin-top:10px">
                    @foreach([100000, 200000, 500000, 1000000, 2000000, 5000000] as $preset)
                        @continue($preset < $min)
                        <button type="button" data-value="{{ $preset }}">{{ number_format($preset, 0, ',', '.') }}</button>
                    @endforeach
                </div>
                <div class="form-actions"><button class="btn btn-primary btn-lg btn-block" type="submit">Tạo yêu cầu nạp tiền</button></div>
                <p class="hint" style="margin-top:8px">Sau khi tạo yêu cầu, hệ thống sinh nội dung chuyển khoản riêng để admin đối soát nhanh.</p>
            </form>
            <div>
                <div class="bank-card">
                    <div><span>Ngân hàng</span><strong>{{ site_setting('bank_name') }}</strong></div>
                    <div><span>Số tài khoản</span><strong>{{ site_setting('bank_account') }}</strong></div>
                    <div><span>Chủ tài khoản</span><strong>{{ site_setting('bank_owner') }}</strong></div>
                    <div><span>Chi nhánh</span><strong>{{ site_setting('bank_branch') }}</strong></div>
                </div>
                @if($pending = $topups->firstWhere('status', 'pending'))
                    <div class="box mt-24">
                        <h3>Yêu cầu #{{ $pending->id }} đang chờ</h3>
                        <p style="font-size:.92rem">Chuyển <strong style="color:var(--red)">{{ cms_money($pending->amount) }}</strong> với nội dung:</p>
                        <p style="display:flex;gap:8px;align-items:center;margin-top:8px"><code style="padding:6px 10px;background:var(--bg);border-radius:4px">{{ $pending->transfer_note }}</code>
                            <button class="btn btn-outline btn-sm" type="button" data-copy="{{ $pending->transfer_note }}"><i class="far fa-copy"></i> Sao chép</button></p>
                    </div>
                @endif
            </div>
        </div>

        <h2 style="margin:28px 0 12px;font-size:1.2rem">Yêu cầu nạp tiền gần đây</h2>
        <div class="table-scroll"><table class="price-table">
            <thead><tr><th>Mã</th><th>Số tiền</th><th>Nội dung CK</th><th>Thời gian</th><th>Trạng thái</th></tr></thead>
            <tbody>
            @forelse($topups as $topup)
                <tr>
                    <td>#{{ $topup->id }}</td><td><strong>{{ cms_money($topup->amount) }}</strong></td><td><code>{{ $topup->transfer_note }}</code></td>
                    <td>{{ \Illuminate\Support\Carbon::parse($topup->created_at)->format('d/m/Y H:i') }}</td>
                    <td><span class="status {{ ['pending' => 'pending', 'approved' => 'approved', 'rejected' => 'rejected'][$topup->status] }}">{{ ['pending' => 'Chờ xác nhận', 'approved' => 'Đã cộng tiền', 'rejected' => 'Từ chối'][$topup->status] }}</span>
                        @if($topup->admin_note)<br><small class="hint">{{ $topup->admin_note }}</small>@endif</td>
                </tr>
            @empty
                <tr><td colspan="5" class="center hint">Chưa có yêu cầu nạp tiền.</td></tr>
            @endforelse
            </tbody>
        </table></div>

        <h2 style="margin:28px 0 12px;font-size:1.2rem">Lịch sử biến động số dư</h2>
        <div class="table-scroll"><table class="price-table">
            <thead><tr><th>Thời gian</th><th>Loại</th><th>Nội dung</th><th>Số tiền</th><th>Số dư</th></tr></thead>
            <tbody>
            @forelse($transactions as $row)
                <tr>
                    <td>{{ \Illuminate\Support\Carbon::parse($row->created_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ config('cms.wallet_types.'.$row->type, $row->type) }}</td>
                    <td>{{ $row->description }}</td>
                    <td class="{{ $row->amount >= 0 ? 'money-in' : 'money-out' }}">{{ $row->amount >= 0 ? '+' : '−' }}{{ cms_money(abs($row->amount)) }}</td>
                    <td>{{ cms_money($row->balance_after) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="center hint">Chưa có giao dịch.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        {{ $transactions->onEachSide(1)->links('client::pagination') }}
    </div>
</div>
@endsection
