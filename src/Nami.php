<?php

namespace Zoomyboy\LaravelNami;

use Illuminate\Support\Facades\Facade;

class Nami extends Facade {
    protected static function getFacadeAccessor() { return 'nami.api'; }
}
