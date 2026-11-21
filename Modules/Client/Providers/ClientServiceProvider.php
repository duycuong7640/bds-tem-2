<?php

namespace Modules\Client\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class ClientServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../views', 'client');
        Route::middleware('web')->group(__DIR__.'/../routes/web.php');
    }
}
