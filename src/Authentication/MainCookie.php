<?php

namespace Zoomyboy\LaravelNami\Authentication;

use Carbon\Carbon;
use GuzzleHttp\Cookie\FileCookieJar;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Zoomyboy\LaravelNami\LoginException;

class MainCookie extends Authenticator {

    private string $path = __DIR__.'/../../.cookies';
    private FileCookieJar $cookie;
    private string $url = 'https://nami.dpsg.de';

    public function login(int $mglnr, string $password): self
    {
        if ($this->isLoggedIn()) {
            return $this;
        }

        while ($file = $this->file()) {
            unlink($file);
        }

        $this->http()->get($this->url.'/ica/pages/login.jsp');
        $response = $this->http()->asForm()->post($this->url.'/ica/rest/nami/auth/manual/sessionStartup', [
            'Login' => 'API',
            'redirectTo' => './app.jsp',
            'username' => $mglnr,
            'password' => $password
        ]);

        if ($response->json()['statusCode'] !== 0) {
            $e = new LoginException();
            $e->setResponse($response->json());
            throw $e;
        }

        $this->cookie->save($this->newFileName());

        return $this;
    }

    public function isLoggedIn(): bool
    {
        if ($this->file() === null) {
            return false;
        }

        return ! $this->isExpired();
    }

    public function http(): PendingRequest
    {
        return Http::withOptions(['cookies' => $this->load()]);
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

    /**
     * Loads the cookie for a new request
     *
     * @return FileCookieJar
     */
    private function load(): FileCookieJar
    {
        $cookieFile = $this->file() ?: $this->newFileName();

        return $this->cookie = new FileCookieJar($cookieFile);
    }

}
