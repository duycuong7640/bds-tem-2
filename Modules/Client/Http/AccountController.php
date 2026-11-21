<?php

namespace Modules\Client\Http;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Modules\Client\Services\MemberListingService;
use Modules\Client\Services\WebsiteService;

class AccountController
{
    public function __construct(private MemberListingService $listings, private WebsiteService $website) {}

    public function dashboard(Request $request): View
    {
        $user = $request->user();
        $status = in_array($request->query('trang-thai'), ['pending', 'approved', 'rejected', 'expired'], true) ? $request->query('trang-thai') : null;
        $now = now()->toDateTimeString();
        $base = fn () => DB::table('products')->where('user_id', $user->id);
        $records = $base()->leftJoin('listing_packages', 'listing_packages.id', '=', 'products.package_id')
            ->select('products.*', 'listing_packages.name as package_name', 'listing_packages.badge as package_badge', 'listing_packages.color as package_color')
            ->when($status === 'expired', fn ($q) => $q->where('products.status', 'approved')->where('products.expires_at', '<=', $now))
            ->when($status === 'approved', fn ($q) => $q->where('products.status', 'approved')->where(fn ($l) => $l->whereNull('products.expires_at')->orWhere('products.expires_at', '>', $now)))
            ->when(in_array($status, ['pending', 'rejected'], true), fn ($q) => $q->where('products.status', $status))
            ->latest('products.id')->paginate(10)->withQueryString();
        $counts = [
            'all' => $base()->count(),
            'approved' => $base()->where('status', 'approved')->where(fn ($l) => $l->whereNull('expires_at')->orWhere('expires_at', '>', $now))->count(),
            'pending' => $base()->where('status', 'pending')->count(),
            'rejected' => $base()->where('status', 'rejected')->count(),
            'expired' => $base()->where('status', 'approved')->where('expires_at', '<=', $now)->count(),
        ];

        return view('client::account.dashboard', compact('records', 'counts', 'status'));
    }

    public function create(): View
    {
        return view('client::account.listing-form', $this->formData(null));
    }

    public function store(Request $request): RedirectResponse
    {
        $listing = $this->listings->save($request, $request->user());

        return to_route('client.account')->with('status', $listing->status === 'approved'
            ? 'Đăng tin thành công! Tin #'.$listing->id.' đã hiển thị trên website.'
            : 'Đã thanh toán và gửi tin #'.$listing->id.'. Tin sẽ hiển thị sau khi được kiểm duyệt.');
    }

    public function edit(Request $request, int $id): View
    {
        return view('client::account.listing-form', $this->formData($this->listings->owned($request->user()->id, $id)));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $listing = $this->listings->save($request, $request->user(), $this->listings->owned($request->user()->id, $id));

        return to_route('client.account')->with('status', $listing->status === 'approved'
            ? 'Đã cập nhật tin #'.$listing->id.'.' : 'Đã cập nhật tin #'.$listing->id.'. Tin sẽ được kiểm duyệt lại trước khi hiển thị.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $this->listings->delete($request->user(), $id);

        return to_route('client.account')->with('status', 'Đã xóa tin #'.$id.'.');
    }

    private function formData(?object $listing): array
    {
        return [
            'listing' => $listing,
            'categories' => $this->website->categories(),
            'packages' => $this->website->packages(),
            'paying' => $this->listings->needsPayment($listing),
            'gallery' => $listing ? (json_decode($listing->gallery ?? '[]', true) ?: []) : [],
            'maxImages' => MemberListingService::MAX_IMAGES,
        ];
    }

    public function wallet(Request $request): View
    {
        $user = $request->user();
        $transactions = DB::table('wallet_transactions')->where('user_id', $user->id)->latest('id')->paginate(10, ['*'], 'gd');
        $topups = DB::table('topup_requests')->where('user_id', $user->id)->latest('id')->limit(10)->get();
        $prefix = site_setting('transfer_prefix') ?: Str::upper(Str::slug(site_setting('site_name', config('app.name')), ''));

        return view('client::account.wallet', compact('transactions', 'topups', 'prefix'));
    }

    public function topup(Request $request): RedirectResponse
    {
        $user = $request->user();
        $min = (int) site_setting('min_topup', 50000);
        $data = $request->validateWithBag('topup', [
            'amount' => ['required', 'integer', 'min:'.$min, 'max:1000000000'],
            'transfer_note' => 'nullable|string|max:255',
        ], [
            'amount.required' => 'Vui lòng nhập hoặc chọn số tiền cần nạp.', 'amount.integer' => 'Số tiền phải là số nguyên (đồng).',
            'amount.min' => 'Số tiền nạp tối thiểu '.cms_money($min).'.', 'amount.max' => 'Số tiền nạp tối đa 1 tỷ đồng mỗi lần.',
        ]);
        if (DB::table('topup_requests')->where('user_id', $user->id)->where('status', 'pending')->count() >= 3) {
            return back()->withErrors(['amount' => 'Bạn đang có 3 yêu cầu chờ duyệt. Vui lòng chờ xử lý trước khi tạo thêm.'], 'topup')->withInput();
        }
        $prefix = site_setting('transfer_prefix') ?: Str::upper(Str::slug(site_setting('site_name', config('app.name')), ''));
        $id = DB::table('topup_requests')->insertGetId([
            'user_id' => $user->id, 'amount' => $data['amount'], 'method' => 'bank',
            'transfer_note' => $prefix.' '.$user->phone, 'status' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('topup_requests')->where('id', $id)->update(['transfer_note' => $prefix.' NAP'.$id.' '.$user->phone]);

        return to_route('client.wallet')->with('status', 'Đã tạo yêu cầu nạp #'.$id.'. Vui lòng chuyển khoản đúng nội dung “'.$prefix.' NAP'.$id.' '.$user->phone.'”, tiền sẽ vào ví sau khi admin xác nhận.');
    }

    public function profile(): View
    {
        return view('client::account.profile');
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validateWithBag('profile', [
            'name' => 'required|string|min:2|max:120',
            'phone' => ['required', 'string', 'regex:/^(0|\+84)[0-9]{9,10}$/', Rule::unique('users', 'phone')->ignore($user->id)],
        ], [
            'name.required' => 'Vui lòng nhập họ tên.', 'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại phải bắt đầu bằng 0 hoặc +84 và gồm 10–11 chữ số.', 'phone.unique' => 'Số điện thoại đã được dùng cho tài khoản khác.',
        ]);
        DB::table('users')->where('id', $user->id)->update($data + ['updated_at' => now()]);

        return to_route('client.profile')->with('status', 'Đã cập nhật thông tin cá nhân.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validateWithBag('password', [
            'current_password' => 'required|current_password',
            'password' => ['required', 'confirmed', 'max:72', Password::min(8)->letters()->numbers()],
        ], [
            'current_password.required' => 'Vui lòng nhập mật khẩu hiện tại.', 'current_password.current_password' => 'Mật khẩu hiện tại không đúng.',
            'password.required' => 'Vui lòng nhập mật khẩu mới.', 'password.confirmed' => 'Mật khẩu nhập lại không khớp.',
            'password.min' => 'Mật khẩu tối thiểu 8 ký tự.', 'password.letters' => 'Mật khẩu cần có ít nhất 1 chữ cái.', 'password.numbers' => 'Mật khẩu cần có ít nhất 1 chữ số.',
        ]);
        DB::table('users')->where('id', $user->id)->update(['password' => Hash::make($data['password']), 'updated_at' => now()]);
        $request->session()->regenerate();

        return to_route('client.profile')->with('status', 'Đã đổi mật khẩu.');
    }
}
