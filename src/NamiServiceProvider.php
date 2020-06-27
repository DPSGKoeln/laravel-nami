<?php

namespace Zoomyboy\LaravelNami;

use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\ServiceProvider;

class NamiServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Auth::provider('nami', function ($app, array $config) {
            return new NamiUserProvider($config['model']);
        });
    }

    public function register() {
        $this->app->bind('nami.api', function() {
            return new Api();
        });
    }
}
