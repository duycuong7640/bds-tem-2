<?php

namespace Modules\Admin\Http;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateMediaDirectory
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(in_array($request->input('type'), [null, '', 'image', 'images', 'file', 'files'], true), 400);
        $directory = $request->input('working_dir');
        abort_unless($directory === null || $directory === '' || (
            is_string($directory) && strlen($directory) <= 512 && preg_match('~^/library(?:/[a-z0-9_-]+)*/?$~iD', $directory)
        ), 400);

        return $next($request);
    }
}
