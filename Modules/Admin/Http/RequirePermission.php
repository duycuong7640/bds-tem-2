<?php

namespace Modules\Admin\Http;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $permission = str_replace('{resource}', (string) $request->route('resource'), $permission);
        abort_unless(admin_can($permission), 403);

        return $next($request);
    }
}
