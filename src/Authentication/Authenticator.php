<?php

namespace Zoomyboy\LaravelNami\Authentication;

use Illuminate\Http\Client\PendingRequest;

abstract class Authenticator {

    abstract public function login(int $mglnr, string $password): self;
    abstract public function http(): PendingRequest;
    abstract public function isLoggedIn(): bool;

}
