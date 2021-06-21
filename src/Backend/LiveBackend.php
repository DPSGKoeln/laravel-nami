<?php

namespace Zoomyboy\LaravelNami\Backend;

use Illuminate\Support\Facades\Http;
use Zoomyboy\LaravelNami\Cookies\Cookie;

class LiveBackend {

    public static function init($cookie) {
        return Http::withOptions(['cookies' => $cookie->forBackend()]);
    }

}
