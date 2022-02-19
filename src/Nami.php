<?php

namespace Zoomyboy\LaravelNami;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Zoomyboy\LaravelNami\Api login(int $mglnr, string $password)
 * @method static \Zoomyboy\LaravelNami\Api fake()
 */
class Nami extends Facade {

    protected static function getFacadeAccessor() { return 'nami.api'; }

    protected static function fake(): void
    {
        static::swap(ApiFake::class);
    }

}
