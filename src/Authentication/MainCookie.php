<?php

namespace Zoomyboy\LaravelNami\Authentication;

use Carbon\Carbon;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Zoomyboy\LaravelNami\LoginException;

class MainCookie extends Authenticator
{
    private CookieJar $cookie;
    private string $url = 'https://nami.dpsg.de';
    private ?int $mglnr = null;
    private ?string $password = null;

    public function login(int $mglnr, string $password): self
    {
        if ($this->isLoggedIn()) {
            return $this;
        }

        $this->mglnr = $mglnr;
        $this->password = $password;

        while ($file = $this->file()) {
            unlink($file);
        }

        $this->http()->get($this->url.'/ica/pages/login.jsp');
        $response = $this->http()->asForm()->post($this->url.'/ica/rest/nami/auth/manual/sessionStartup', [
            'Login' => 'API',
            'redirectTo' => './app.jsp',
            'username' => $mglnr,
            'password' => $password,
        ]);

        if (0 !== $response->json()['statusCode']) {
            $e = new LoginException();
            $e->setResponse($response->json());
            throw $e;
        }

        file_put_contents($this->newFileName(), $this->cookie->getCookieByName('JSESSIONID')->getValue());

        return $this;
    }

    public function isLoggedIn(): bool
    {
        if (null === $this->file()) {
            return false;
        }

        return !$this->isExpired();
    }

    public function refresh(): void
    {
        if ($this->mglnr && $this->password) {
            $this->login($this->mglnr, $this->password);
        }
    }

    public function http(): PendingRequest
    {
        return Http::withOptions(['cookies' => $this->load()]);
    }

    private function newFileName(): string
    {
        return parent::$path.'/'.time().'.txt';
    }

    private function isExpired(): bool
    {
        $lastLoginTime = Carbon::createFromTimestamp(pathinfo($this->file(), PATHINFO_FILENAME));

        return $lastLoginTime->addMinutes(50)->isPast();
    }

    /**
     * Get the cookie file if it exists.
     *
     * @return ?string
     */
    private function file(): ?string
    {
        $files = glob(parent::$path.'/*');

        if (!count($files)) {
            return null;
        }

        return $files[0];
    }

    /**
     * Loads the cookie for a new request.
     */
    private function load(): CookieJar
    {
        if ($this->file()) {
            $data = file_get_contents($this->file());

            return $this->cookie = CookieJar::fromArray([
                'JSESSIONID' => $data,
            ], 'nami.dpsg.de');
        }

        return $this->cookie = new CookieJar();
    }
}
