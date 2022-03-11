<?php

namespace Zoomyboy\LaravelNami\Providers;

use Illuminate\Support\ServiceProvider;
use Zoomyboy\LaravelNami\Api;
use Zoomyboy\LaravelNami\Authentication\Authenticator;
use Zoomyboy\LaravelNami\Authentication\MainCookie;

class NamiServiceProvider extends ServiceProvider
{
    public function boot()
    {
    }

    public function register()
    {
        $this->app->singleton(Authenticator::class, function () {
            return app(MainCookie::class);
        });
        $this->app->bind('nami.api', function () {
            return app(Api::class);
        });
    }
}
