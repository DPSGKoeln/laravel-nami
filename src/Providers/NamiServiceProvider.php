<?php

namespace Zoomyboy\LaravelNami\Providers;

use GuzzleHttp\Client as GuzzleClient;
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
        $this->app->singleton('nami.api', function() {
            return new Api($this->app['nami.cookie']);
        });
        $this->app->bind('nami.backend', function() {
            return new LiveBackend();
        });
        $this->app->singleton('nami.cookie', function() {
            return new CacheCookie();
        });
    }
}
