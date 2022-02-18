<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Zoomyboy\LaravelNami\LoginException;
use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\Tests\TestCase;

class LoginTest extends TestCase
{

    public function test_first_successful_login(): void
    {
        Http::fake($this->fakeSuccessfulLogin());

        Nami::login(12345, 'secret');

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request->url() == 'https://nami.dpsg.de/ica/pages/login.jsp');
        Http::assertSent(fn ($request) => $request->url() == 'https://nami.dpsg.de/ica/rest/nami/auth/manual/sessionStartup'
            && $request['username'] == '12345' && $request['password'] == 'secret'
            && $request['redirectTo'] == './app.jsp' && $request['Login'] == 'API'
        );
    }

    public function test_it_throws_exception_when_login_failed(): void
    {
        Http::fake($this->fakeFailedLogin());

        try {
            Nami::login(12345, 'wrongpassword');
        } catch (LoginException $e) {
            $this->assertEquals(LoginException::WRONG_CREDENTIALS, $e->reason);
        }
    }

    public function test_first_login_fails_because_of_bruteforce_protection(): void
    {
        Http::fake($this->fakeBruteforceFailure());

        try {
            Nami::login(12345, 'secret');
        } catch (LoginException $e) {
            $this->assertEquals(LoginException::TOO_MANY_FAILED_LOGINS, $e->reason);
        }
    }

    public function test_store_cookie_after_login(): void
    {
        Http::fake($this->fakeSuccessfulLogin());

        Nami::login(12345, 'secret');

        $this->assertFileExists(__DIR__.'/../../.cookies/'.time().'.txt');
    }

    public function test_dont_login_if_cookie_exists(): void
    {
        touch(__DIR__.'/../../.cookies/'.time().'.txt');

        Nami::login(12345, 'secret');

        Http::assertSentCount(0);
    }

    public function test_delete_expired_cookie_before_login(): void
    {
        $lastLogin = now()->subHours(2)->timestamp;
        touch(__DIR__."/../../.cookies/{$lastLogin}.txt");
        Http::fake($this->fakeSuccessfulLogin());

        Nami::login(12345, 'secret');

        Http::assertSentCount(2);
        $this->assertFileDoesNotExist(__DIR__."/../../.cookies/{$lastLogin}.txt");
    }

    public function test_login_once_if_cookie_is_expired(): void
    {
        $lastLogin = now()->subHour()->subMinutes(10)->timestamp;
        touch(__DIR__."/../../.cookies/{$lastLogin}.txt");
        Http::fake($this->fakeSuccessfulLogin());

        Nami::login(12345, 'secret');
        Nami::login(12345, 'secret');

        Http::assertSentCount(2);
    }

    private function fakeSuccessfulLogin(): array
    {
        return [
            'https://nami.dpsg.de/ica/pages/login.jsp' => Http::sequence()->push('<html></html>', 200),
            'https://nami.dpsg.de/ica/rest/nami/auth/manual/sessionStartup' => Http::sequence()->push($this->successJson, 200),
        ];
    }

    private function fakeFailedLogin(): array
    {
        return [
            'https://nami.dpsg.de/ica/pages/login.jsp' => Http::sequence()->push('<html></html>', 200),
            'https://nami.dpsg.de/ica/rest/nami/auth/manual/sessionStartup' => Http::sequence()->push($this->wrongCredentialsJson, 200)
        ];
    }

    private function fakeBruteforceFailure(): array
    {
        return [
            'https://nami.dpsg.de/ica/pages/login.jsp' => Http::sequence()->push('<html></html>', 200),
            'https://nami.dpsg.de/ica/rest/nami/auth/manual/sessionStartup' => Http::sequence()->push($this->bruteJson, 200)
        ];
    }

}
