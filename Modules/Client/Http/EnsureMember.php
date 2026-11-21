<?php

namespace Modules\Client\Http;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Khu vực tài khoản chỉ dành cho thành viên đăng tin; tài khoản quản trị được đưa về trang quản trị. */
class EnsureMember
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! is_member($user)) {
            return redirect()->route('admin.home');
        }
        if (! $user->active) {
            auth()->logout();

            return redirect()->route('client.login')->withErrors(['login' => 'Tài khoản đã bị khóa. Vui lòng liên hệ hotline.']);
        }

        return $next($request);
    }
}
