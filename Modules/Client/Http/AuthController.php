<?php

namespace Modules\Client\Http;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Modules\Admin\Services\MarketplaceService;

class AuthController
{
    public function register(Request $request, MarketplaceService $marketplace): RedirectResponse
    {
        $request->merge(['email' => Str::lower(trim((string) $request->input('email')))]);
        $data = $request->validate([
            'name' => 'required|string|min:2|max:120',
            'phone' => ['required', 'string', 'regex:/^(0|\+84)[0-9]{9,10}$/', Rule::unique('users', 'phone')],
            'email' => ['required', 'email:rfc', 'max:254', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', 'max:72', Password::min(8)->letters()->numbers()],
            'agree' => 'accepted',
        ], [
            'name.required' => 'Vui lòng nhập họ tên.', 'name.min' => 'Họ tên tối thiểu 2 ký tự.',
            'phone.required' => 'Vui lòng nhập số điện thoại.', 'phone.regex' => 'Số điện thoại phải bắt đầu bằng 0 hoặc +84 và gồm 10–11 chữ số.',
            'phone.unique' => 'Số điện thoại này đã được đăng ký.',
            'email.required' => 'Vui lòng nhập email.', 'email.email' => 'Email không hợp lệ.', 'email.unique' => 'Email này đã được đăng ký.',
            'password.required' => 'Vui lòng nhập mật khẩu.', 'password.confirmed' => 'Mật khẩu nhập lại không khớp.',
            'password.min' => 'Mật khẩu tối thiểu 8 ký tự.', 'password.letters' => 'Mật khẩu cần có ít nhất 1 chữ cái.',
            'password.numbers' => 'Mật khẩu cần có ít nhất 1 chữ số.',
            'agree.accepted' => 'Bạn cần đồng ý với điều khoản sử dụng.',
        ]);

        $id = DB::table('users')->insertGetId([
            'name' => $data['name'], 'phone' => $data['phone'], 'email' => $data['email'],
            'password' => Hash::make($data['password']), 'role' => config('cms.member_role'), 'active' => true,
            'balance' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $bonus = (int) site_setting('signup_bonus', 0);
        if ($bonus > 0) {
            $marketplace->credit($id, $bonus, 'bonus', 'Quà tặng thành viên mới', ['created_by' => $id]);
        }
        Auth::loginUsingId($id);
        $request->session()->regenerate();

        return to_route('client.account')->with('status', 'Đăng ký thành công! '.($bonus > 0 ? 'Bạn được tặng '.cms_money($bonus).' vào ví để đăng tin.' : ''));
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'login' => 'required|string|max:254',
            'password' => 'required|string|max:72',
        ], ['login.required' => 'Vui lòng nhập email hoặc số điện thoại.', 'password.required' => 'Vui lòng nhập mật khẩu.']);
        $field = str_contains($data['login'], '@') ? 'email' : 'phone';
        $credentials = [
            $field => $field === 'email' ? Str::lower(trim($data['login'])) : trim($data['login']),
            'password' => $data['password'], 'active' => true, 'role' => config('cms.member_role'),
        ];
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['login' => 'Thông tin đăng nhập không đúng hoặc tài khoản đã bị khóa.']);
        }
        $request->session()->regenerate();

        return redirect()->intended(route('client.account'))->with('status', 'Chào mừng bạn quay lại!');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('home')->with('status', 'Bạn đã đăng xuất.');
    }
}
