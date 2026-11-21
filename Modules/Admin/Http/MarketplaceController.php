<?php

namespace Modules\Admin\Http;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Admin\Repositories\CmsRepository;
use Modules\Admin\Services\MarketplaceService;

class MarketplaceController
{
    public function __construct(private MarketplaceService $marketplace, private CmsRepository $repository) {}

    private function members()
    {
        return DB::table('users')->where('role', config('cms.member_role'));
    }

    public function membersIndex(Request $request): View
    {
        $query = mb_substr(trim($request->string('q')->toString()), 0, 120);
        $records = $this->members()
            ->when($query !== '', fn ($builder) => $builder->where(fn ($search) => $search
                ->where('name', 'like', '%'.$query.'%')->orWhere('email', 'like', '%'.$query.'%')->orWhere('phone', 'like', '%'.$query.'%')))
            ->select('users.*')
            ->selectSub(DB::table('products')->whereColumn('products.user_id', 'users.id')->selectRaw('count(*)'), 'listing_count')
            ->latest('id')->paginate(15)->withQueryString();

        return view('admin::members', compact('records', 'query'));
    }

    public function memberEdit(int $id): View
    {
        $member = $this->members()->where('id', $id)->first();
        abort_unless($member, 404);
        $listings = DB::table('products')->where('user_id', $id)->latest('id')->limit(10)->get();
        $transactions = DB::table('wallet_transactions')->where('user_id', $id)->latest('id')->limit(15)->get();
        $spent = (int) -DB::table('wallet_transactions')->where('user_id', $id)->where('type', 'listing_fee')->sum('amount');

        return view('admin::member-form', compact('member', 'listings', 'transactions', 'spent'));
    }

    public function memberUpdate(Request $request, int $id): RedirectResponse
    {
        $member = $this->members()->where('id', $id)->first();
        abort_unless($member, 404);
        $request->merge(['email' => Str::lower((string) $request->input('email'))]);
        $fields = $request->validate([
            'name' => 'required|string|max:120',
            'email' => ['required', 'email:rfc', 'max:254', Rule::unique('users', 'email')->ignore($id)],
            'phone' => ['required', 'regex:/^(0|\+84)[0-9]{9,10}$/', Rule::unique('users', 'phone')->ignore($id)],
            'active' => 'required|boolean',
            'password' => 'nullable|string|min:8|max:72',
        ], [
            'name.required' => 'Vui lòng nhập họ tên.', 'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.', 'email.unique' => 'Email đã được dùng cho tài khoản khác.',
            'phone.required' => 'Vui lòng nhập số điện thoại.', 'phone.regex' => 'Số điện thoại phải bắt đầu bằng 0 hoặc +84 và gồm 10–11 chữ số.',
            'phone.unique' => 'Số điện thoại đã được dùng cho tài khoản khác.', 'password.min' => 'Mật khẩu tối thiểu 8 ký tự.',
        ]);
        if (! empty($fields['password']) || ! $fields['active']) {
            $fields['remember_token'] = Str::random(60);
            $this->repository->revokeSessions($id);
        }
        if (! empty($fields['password'])) {
            $fields['password'] = Hash::make($fields['password']);
        } else {
            unset($fields['password']);
        }
        DB::table('users')->where('id', $id)->update($fields + ['updated_at' => now()]);

        return to_route('admin.members.edit', $id)->with('status', 'Đã cập nhật thành viên.');
    }

    public function memberAdjust(Request $request, int $id): RedirectResponse
    {
        abort_unless($this->members()->where('id', $id)->exists(), 404);
        $data = $request->validateWithBag('adjust', [
            'direction' => 'required|in:add,subtract',
            'amount' => 'required|integer|min:1000|max:1000000000',
            'note' => 'required|string|max:200',
        ], [
            'amount.required' => 'Vui lòng nhập số tiền.', 'amount.min' => 'Số tiền tối thiểu 1.000 ₫.',
            'amount.integer' => 'Số tiền phải là số nguyên.', 'note.required' => 'Vui lòng nhập lý do điều chỉnh.',
        ]);
        $description = 'Admin điều chỉnh: '.$data['note'];
        try {
            $data['direction'] === 'add'
                ? $this->marketplace->credit($id, (int) $data['amount'], 'adjust', $description)
                : $this->marketplace->debit($id, (int) $data['amount'], 'adjust', $description);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return back()->withErrors(['amount' => $exception->errors()['balance'][0] ?? 'Không thể điều chỉnh số dư.'], 'adjust')->withInput();
        }

        return to_route('admin.members.edit', $id)->with('status', 'Đã điều chỉnh số dư.');
    }

    public function topups(Request $request): View
    {
        $status = in_array($request->query('status'), ['pending', 'approved', 'rejected'], true) ? $request->query('status') : null;
        $records = DB::table('topup_requests')->join('users', 'users.id', '=', 'topup_requests.user_id')
            ->when($status, fn ($builder) => $builder->where('topup_requests.status', $status))
            ->select('topup_requests.*', 'users.name as member_name', 'users.email as member_email', 'users.phone as member_phone')
            ->orderByRaw("topup_requests.status = 'pending' desc")->latest('topup_requests.id')->paginate(15)->withQueryString();
        $counts = DB::table('topup_requests')->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->all();

        return view('admin::topups', compact('records', 'status', 'counts'));
    }

    public function approveTopup(Request $request, int $id): RedirectResponse
    {
        $note = $request->validate(['admin_note' => 'nullable|string|max:255'])['admin_note'] ?? null;
        $this->marketplace->approveTopup($id, $note);

        return back()->with('status', 'Đã duyệt và cộng tiền vào ví thành viên.');
    }

    public function rejectTopup(Request $request, int $id): RedirectResponse
    {
        $note = $request->validateWithBag('topup'.$id, ['admin_note' => 'required|string|max:255'], [
            'admin_note.required' => 'Nhập lý do từ chối.',
        ])['admin_note'];
        $this->marketplace->rejectTopup($id, $note);

        return back()->with('status', 'Đã từ chối yêu cầu nạp tiền.');
    }

    public function transactions(Request $request): View
    {
        $type = array_key_exists((string) $request->query('type'), config('cms.wallet_types')) ? $request->query('type') : null;
        $query = mb_substr(trim($request->string('q')->toString()), 0, 120);
        $records = DB::table('wallet_transactions')->join('users', 'users.id', '=', 'wallet_transactions.user_id')
            ->when($type, fn ($builder) => $builder->where('wallet_transactions.type', $type))
            ->when($query !== '', fn ($builder) => $builder->where(fn ($search) => $search
                ->where('users.email', 'like', '%'.$query.'%')->orWhere('users.phone', 'like', '%'.$query.'%')->orWhere('users.name', 'like', '%'.$query.'%')))
            ->select('wallet_transactions.*', 'users.name as member_name', 'users.email as member_email')
            ->latest('wallet_transactions.id')->paginate(20)->withQueryString();
        $totals = [
            'topup' => (int) DB::table('wallet_transactions')->where('type', 'topup')->sum('amount'),
            'fee' => (int) -DB::table('wallet_transactions')->where('type', 'listing_fee')->sum('amount'),
            'refund' => (int) DB::table('wallet_transactions')->where('type', 'refund')->sum('amount'),
            'balance' => (int) $this->members()->sum('balance'),
        ];

        return view('admin::transactions', compact('records', 'type', 'query', 'totals'));
    }

    public function approveListing(int $id): RedirectResponse
    {
        $this->marketplace->approve($id);

        return back()->with('status', 'Đã duyệt tin #'.$id.'.');
    }
}
