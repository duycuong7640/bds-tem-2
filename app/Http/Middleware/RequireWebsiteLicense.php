<?php

namespace App\Http\Middleware;

use App\Services\LicenseGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireWebsiteLicense
{
    public function __construct(private LicenseGate $license) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (config('license.required') && ! $request->is('up') && ! $this->license->allows($request->getHost())) {
            $response = $request->expectsJson() || $request->is('api/*')
                ? response()->json(['message' => 'Website chưa có giấy phép hoạt động hợp lệ.'], 503)
                : response()->view('errors.page', ['status' => 503, 'licenseUnavailable' => true], 503);

            return $response->header('Cache-Control', 'no-store')
                ->header('X-Robots-Tag', 'noindex, nofollow');
        }

        return $next($request);
    }
}
