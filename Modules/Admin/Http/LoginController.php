<?php

namespace Modules\Admin\Http;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController
{
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate(['email' => 'required|email', 'password' => 'required|string'], [
            'email.required' => 'Vui lòng nhập email.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
        ]);
        $credentials['email'] = mb_strtolower($credentials['email']);
        $staff = fn ($query) => $query->whereIn('role', array_keys(config('cms.roles')));
        if (! Auth::attempt($credentials + ['active' => true, $staff], $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'Email, mật khẩu hoặc trạng thái tài khoản không hợp lệ.']);
        }
        $request->session()->regenerate();

        return to_route('admin.home');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('admin.login');
    }
}
