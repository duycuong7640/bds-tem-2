<?php

use App\Http\Middleware\RequireWebsiteLicense;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Modules\Admin\Http\RequirePermission;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(RequireWebsiteLicense::class);
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('admin', 'admin/*') ? '/admin/login' : '/dang-nhap');
        $middleware->redirectUsersTo(fn (Request $request) => is_member($request->user()) ? '/tai-khoan' : '/admin');
        $middleware->alias(['permission' => RequirePermission::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request): Response {
            $status = $response->getStatusCode();
            if ($status < 400 || $request->expectsJson() || $request->is('api/*')
                || ! str_starts_with((string) $response->headers->get('Content-Type'), 'text/html')) {
                return $response;
            }

            $response->headers->remove('Content-Length');
            $response->headers->set('Cache-Control', 'no-store');

            return $response->setContent(view('errors.page', ['status' => $status])->render());
        });
    })->create();
