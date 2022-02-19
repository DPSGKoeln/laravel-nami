<?php

namespace Zoomyboy\LaravelNami\Authentication;

use Carbon\Carbon;
use GuzzleHttp\Cookie\FileCookieJar;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Assert;
use Zoomyboy\LaravelNami\LoginException;

class FakeCookie extends Authenticator {

    private array $validAccounts = [];
    public ?array $invalidAccounts = null;
    public ?array $authenticated = null;

    public function login(int $mglnr, string $password): self
    {
        $authenticated = collect($this->validAccounts)->search(
            fn ($account) => $account['mglnr'] === $mglnr && $account['password'] === $password
        );

        if ($authenticated !== false) {
            $this->authenticated = ['mglnr' => $mglnr, 'password' => $password];
        } else {
            $e = new LoginException();
            $e->setResponse(['statusMessage' => "Benutzer nicht gefunden oder Passwort falsch"]);

            throw $e;
        }

        return $this;
    }

    public function http(): PendingRequest
    {
        return Http::withOptions([]);
    }

    /**
     * Reisters an account that can successfully login with
     * the given password
     *
     * @param int $mglnr
     * @param string $password
     *
     * @return void
     */
    public function success(int $mglnr, string $password): void
    {
        $this->validAccounts[] = ['mglnr' => $mglnr, 'password' => $password];
    }

    /**
     * Reisters an account that cannot login with the given password
     *
     * @param int $mglnr
     * @param string $password
     *
     * @return void
     */
    public function failed(int $mglnr, string $password): void
    {
        $this->invalidAccounts[] = ['mglnr' => $mglnr, 'password' => $password];
    }

    public function assertLoggedInWith(int $mglnr, string $password): void
    {
        Assert::assertSame($mglnr, data_get($this->authenticated, 'mglnr'));
        Assert::assertSame($password, data_get($this->authenticated, 'password'));
    }

    public function assertNotLoggedInWith(int $mglnr, string $password): void
    {
        Assert::assertTrue(
            $mglnr !== data_get($this->authenticated, 'mglnr')
            || $password !== data_get($this->authenticated, 'password'),
            "Failed asserting that user {$mglnr} is not loggedd in with {$password}"
        );
    }

    public function assertNotLoggedIn(): void
    {
        Assert::assertNull(
            $this->authenticated,
            'Failed asserting that noone is logged in. Found login with '.data_get($this->authenticated, 'mglnr')
        );
    }

    public function assertLoggedIn(): void
    {
        
    }

}
