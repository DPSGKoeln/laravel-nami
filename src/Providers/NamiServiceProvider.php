<?php

namespace Zoomyboy\LaravelNami\Providers;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Cookie\CookieJarInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Zoomyboy\LaravelNami\Api;
use Zoomyboy\LaravelNami\Authentication\NamiGuard;
use Zoomyboy\LaravelNami\Backend\LiveBackend;
use Zoomyboy\LaravelNami\Cookies\CacheCookie;

class NamiServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Auth::extend('nami', function ($app, $name, array $config) {
            return (new NamiGuard($this->app['session.store'], $this->app['cache.store']))
                ->setFallbacks(data_get($config, 'other_providers', []));
        });
    }

    public function register() {
        $this->app->bind('nami.api', function() {
            return app(Api::class);
        });
    }
}
