<?php

namespace Zoomyboy\LaravelNami\Providers;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Cookie\CookieJarInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Zoomyboy\LaravelNami\Api;
use Zoomyboy\LaravelNami\Authentication\Authenticator;
use Zoomyboy\LaravelNami\Authentication\MainCookie;
use Zoomyboy\LaravelNami\Backend\LiveBackend;
use Zoomyboy\LaravelNami\Cookies\CacheCookie;

class NamiServiceProvider extends ServiceProvider
{
    public function boot()
    {
        //
    }

    public function register() {
        $this->app->singleton(Authenticator::class, function() {
            return app(MainCookie::class);
        });
        $this->app->bind('nami.api', function() {
            return app(Api::class);
        });
    }
}
