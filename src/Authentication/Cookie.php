<?php

namespace Zoomyboy\LaravelNami\Authentication;

use Carbon\Carbon;
use GuzzleHttp\Cookie\FileCookieJar;

class Cookie {

    public string $path = __DIR__.'/../../.cookies';
    private FileCookieJar $cookie;

    /**
     * Loads the cookie for a new request
     *
     * @return FileCookieJar
     */
    public function load(): FileCookieJar
    {
        $cookieFile = $this->file() ?: $this->newFileName();

        return $this->cookie = new FileCookieJar($cookieFile);
    }

    /**
     * Clears all cookies before logging in
     *
     * @return void
     */
    public function beforeLogin(): void
    {
        while ($file = $this->file()) {
            unlink($file);
        }
    }

    /**
     * Set last login to now after login
     *
     * @return void
     */
    public function afterLogin(): void
    {
        $this->cookie->save($this->newFileName());
    }

    public function isLoggedIn(): bool
    {
        if ($this->file() === null) {
            return false;
        }

        return ! $this->isExpired();
    }

    private function newFileName(): string
    {
        return $this->path.'/'.time().'.txt';
    }

    private function isExpired(): bool
    {
        $lastLoginTime = Carbon::createFromTimestamp(pathinfo($this->file(), PATHINFO_FILENAME));

        return $lastLoginTime->addMinutes(50)->isPast();
    }

    /**
     * Get the cookie file if it exists
     *
     * @return ?string
     */
    private function file(): ?string
    {
        $files = glob($this->path.'/*');

        if (!count($files)) {
            return null;
        }

        return $files[0];
    }



}
