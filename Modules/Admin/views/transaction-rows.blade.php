<div class="table-scroll"><table>
    <thead><tr><th>Thời gian</th>@if($showMember)<th>Thành viên</th>@endif<th>Loại</th><th>Nội dung</th><th>Số tiền</th><th>Số dư sau</th></tr></thead>
    <tbody>
    @forelse($records as $row)
        <tr>
            <td>{{ \Illuminate\Support\Carbon::parse($row->created_at)->format('d/m/Y H:i') }}</td>
            @if($showMember)<td><a href="{{ route('admin.members.edit', $row->user_id) }}">{{ $row->member_name }}</a><small class="muted-line">{{ $row->member_email }}</small></td>@endif
            <td><span class="badge">{{ config('cms.wallet_types.'.$row->type, $row->type) }}</span></td>
            <td>{{ $row->description }}</td>
            <td class="{{ $row->amount >= 0 ? 'money-in' : 'money-out' }}">{{ $row->amount >= 0 ? '+' : '−' }}{{ cms_money(abs($row->amount)) }}</td>
            <td>{{ cms_money($row->balance_after) }}</td>
        </tr>
    @empty
        <tr><td colspan="6"><p class="empty">Chưa có giao dịch.</p></td></tr>
    @endforelse
    </tbody>
</table></div>
