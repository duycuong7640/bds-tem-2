@extends('admin::layout')
@section('title', 'Giao dịch ví')
@section('content')
<div class="intro"><div><h1>Giao dịch ví</h1><p>Toàn bộ biến động số dư: nạp tiền, phí đăng tin, hoàn tiền, điều chỉnh.</p></div></div>
<div class="member-summary">
    <div><span>Tổng tiền đã nạp</span><strong>{{ cms_money($totals['topup']) }}</strong></div>
    <div><span>Doanh thu phí đăng tin</span><strong>{{ cms_money($totals['fee'] - $totals['refund']) }}</strong></div>
    <div><span>Đã hoàn cho thành viên</span><strong>{{ cms_money($totals['refund']) }}</strong></div>
    <div><span>Tổng số dư ví thành viên</span><strong>{{ cms_money($totals['balance']) }}</strong></div>
</div>
<div class="panel table-panel">
    <nav class="filter-tabs" aria-label="Lọc loại giao dịch">
        <a href="{{ route('admin.transactions', ['q' => $query ?: null]) }}" @if(!$type) aria-current="page" @endif>Tất cả</a>
        @foreach(config('cms.wallet_types') as $key => $label)
            <a href="{{ route('admin.transactions', ['type' => $key, 'q' => $query ?: null]) }}" @if($type === $key) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>
    <form class="list-tools" method="get" action="{{ route('admin.transactions') }}">
        @if($type)<input type="hidden" name="type" value="{{ $type }}">@endif
        <label class="search"><i class="fas fa-search"></i><input name="q" value="{{ $query }}" maxlength="120" placeholder="Tên, email, SĐT thành viên" aria-label="Tìm giao dịch"></label>
        <button class="button">Tìm</button>
        <span class="result-count">{{ $records->total() }} giao dịch</span>
    </form>
    @include('admin::transaction-rows', ['records' => $records, 'showMember' => true])
    {{ $records->onEachSide(1)->links('admin::pagination') }}
</div>
@endsection
