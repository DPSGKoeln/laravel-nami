<?php

namespace Zoomyboy\LaravelNami\Authentication;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void assertNotLoggedIn()
 * @method static void success(int $mglnr, string $password)
 * @method static void failed(int $mglnr, string $password)
 * @method static void assertLoggedInWith(int $mglnr, string $password)
 * @method static void assertNotLoggedInWith(int $mglnr, string $password)
 * @method static void assertLoggedIn()
 */
class Auth extends Facade
{
    public static function getFacadeAccessor()
    {
        return Authenticator::class;
    }

    public static function fake(): Authenticator
    {
        static::swap($fake = app(FakeCookie::class));

        return $fake;
    }
}
