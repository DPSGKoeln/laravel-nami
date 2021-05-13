<?php

namespace Zoomyboy\LaravelNami\Backend;

use Illuminate\Support\Facades\Facade;

class Backend extends Facade {
    protected static function getFacadeAccessor() { return 'nami.backend'; }

    public static function fake() {
        static::swap($api = new FakeBackend());

        return $api;
    }
}
