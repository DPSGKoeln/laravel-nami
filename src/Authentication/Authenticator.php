<?php

namespace Zoomyboy\LaravelNami\Authentication;

use Illuminate\Http\Client\PendingRequest;

abstract class Authenticator
{
    abstract public function login(int $mglnr, string $password): self;

    abstract public function purge(): void;

    abstract public function http(): PendingRequest;

    abstract public function isLoggedIn(): bool;

    abstract public function refresh(): void;

    protected static string $path = __DIR__.'/../../.cookies';

    public static function setPath(string $path): void
    {
        static::$path = $path;
    }
}
