<?php

namespace Zoomyboy\LaravelNami\Cookies;

use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Cookie\SetCookie;

class CacheCookie {

    private $store;
    private $createdAt;

    public function __construct() {
        $this->store = new \GuzzleHttp\Cookie\CookieJar();
    }

    public function forBackend() {
        return $this->store;
        return \GuzzleHttp\Cookie\CookieJar::fromArray(['JSESSIONID' => Cache::get("namicookie-{$mglnr}")], 'nami.dpsg.de');
    }

    /**
     * Store the current cookie in the cache
     */
    public function store($mglnr) {
        Cache::forever("namicookie-{$mglnr}", [
            'cookie' => $this->store->getCookieByName('JSESSIONID')->getValue(),
            'created_at' => now(),
        ]);
        $this->createdAt = now();
    }

    public function isExpired() {
        return $this->createdAt->addHour(1)->isPast();
    }

    /**
     * Set a cookie by string
     */
    public function set($mglnr, $cookie) {
        $this->store->setCookie(tap(SetCookie::fromString('JSESSIONID='.$cookie.'; path=/ica'), function($cookie) {
            $cookie->setDomain('nami.dpsg.de');
        }));
    }

    /**
     * Get the stored cookie from the cache
     */
    public function resolve($mglnr) {
        $cookie = Cache::get("namicookie-{$mglnr}");
        if ($cookie === null) {
            return false;
        }

        $this->set($mglnr, $cookie['cookie']);
        $this->createdAt = $cookie['created_at'];
        return true;
    }

}
