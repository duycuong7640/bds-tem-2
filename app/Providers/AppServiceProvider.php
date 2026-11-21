<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('webfx.runtime')) {
            $url = config('app.url');
            URL::useOrigin($url);
            URL::useAssetOrigin($url);
            URL::forceScheme(parse_url($url, PHP_URL_SCHEME));
            if (parse_url($url, PHP_URL_SCHEME) === 'https') {
                config(['session.secure' => true]);
            }
        }
    }
}
