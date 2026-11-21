<?php

namespace Modules\Admin\Http;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/** Thành viên đăng tin (role member) không được vào trang quản trị. */
class EnsureStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        if (is_member($request->user())) {
            return redirect()->route('client.account')->with('status', 'Tài khoản thành viên không có quyền vào trang quản trị.');
        }

        return $next($request);
    }
}
