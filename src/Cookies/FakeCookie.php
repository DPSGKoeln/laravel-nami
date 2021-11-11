<?php

namespace Zoomyboy\LaravelNami\Cookies;

class FakeCookie {

    private $loggedIn = false;

    public function forBackend() {
        return \GuzzleHttp\Cookie\CookieJar::fromArray([], 'nami.dpsg.de');
    }

    public function store($cookie) {
        $this->loggedIn = true;
    }

    public function resolve($mglnr) {
        return $this->loggedIn;        
    }

    public function isExpired(): bool
    {
        return false;
    }

}
