<?php

namespace Zoomyboy\LaravelNami\Cookies;

use Illuminate\Support\Facades\Facade;

class Cookie extends Facade {

    public static function getFacadeAccessor() {
        return 'nami.cookie';
    }

}
