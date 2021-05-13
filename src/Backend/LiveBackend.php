<?php

namespace Zoomyboy\LaravelNami\Backend;

use Illuminate\Support\Facades\Http;

class LiveBackend {

    public static function cookie($cookie) {
        return Http::withOptions(['cookies' => $cookie]);
    }

}
