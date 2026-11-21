<?php

namespace Modules\Admin\Http;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockDemoAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if(config('webfx.demo'), 403);

        return $next($request);
    }
}
