<?php

namespace Zoomyboy\LaravelNami\Providers;

use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\ServiceProvider;
use Zoomyboy\LaravelNami\Backend\LiveBackend;
use Zoomyboy\LaravelNami\Api;
use Zoomyboy\LaravelNami\Authentication\NamiGuard;

class NamiServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Auth::extend('nami', function ($app, $name, array $config) {
            return new NamiGuard($config);
        });
    }

    public function register() {
        $this->app->singleton('nami.api', function() {
            return new Api();
        });
        $this->app->bind('nami.backend', function() {
            return new LiveBackend();
        });
    }
}
