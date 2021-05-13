<?php

namespace Zoomyboy\LaravelNami\Providers;

use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\ServiceProvider;
use Zoomyboy\LaravelNami\Backend\LiveBackend;
use Zoomyboy\LaravelNami\Api;

class NamiServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Auth::provider('nami', function ($app, array $config) {
            return new NamiUserProvider($config['model']);
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
