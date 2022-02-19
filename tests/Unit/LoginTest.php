<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Zoomyboy\LaravelNami\Authentication\Auth;
use Zoomyboy\LaravelNami\LoginException;
use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\Tests\TestCase;

class LoginTest extends TestCase
{

    public string $successJson = '{"servicePrefix":null,"methodCall":null,"response":null,"statusCode":0,"statusMessage":"","apiSessionName":"JSESSIONID","apiSessionToken":"ILBY--L4pZEjSKa39tCemens","minorNumber":2,"majorNumber":1}';
    public string $bruteJson = '{"servicePrefix":null,"methodCall":null,"response":null,"statusCode":3000,"statusMessage":"Die höchste Anzahl von Login-Versuchen wurde erreicht. Ihr Konto ist für 15 Minuten gesperrt worden. Nach Ablauf dieser Zeitspanne wird ihr Zugang wieder freigegeben.","apiSessionName":"JSESSIONID","apiSessionToken":"tGlSpMMij9ruHfeiUYjO7SD2","minorNumber":0,"majorNumber":0}';
    public string $wrongCredentialsJson = '{"servicePrefix":null,"methodCall":null,"response":null,"statusCode":3000,"statusMessage":"Benutzer nicht gefunden oder Passwort falsch.","apiSessionName":"JSESSIONID","apiSessionToken":"v7lrjgPBbXInJR57qJzVIJ05","minorNumber":0,"majorNumber":0}';

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

        $this->assertFileExists(__DIR__.'/../../.cookies_test/'.time().'.txt');
    }

    public function test_dont_login_if_cookie_exists(): void
    {
        touch(__DIR__.'/../../.cookies_test/'.time().'.txt');

        Nami::login(12345, 'secret');

        Http::assertSentCount(0);
    }

    public function test_delete_expired_cookie_before_login(): void
    {
        $lastLogin = now()->subHours(2)->timestamp;
        touch(__DIR__."/../../.cookies_test/{$lastLogin}.txt");
        Http::fake($this->fakeSuccessfulLogin());

        Nami::login(12345, 'secret');

        Http::assertSentCount(2);
        $this->assertFileDoesNotExist(__DIR__."/../../.cookies_test/{$lastLogin}.txt");
    }

    public function test_login_once_if_cookie_is_expired(): void
    {
        $lastLogin = now()->subHour()->subMinutes(10)->timestamp;
        touch(__DIR__."/../../.cookies_test/{$lastLogin}.txt");
        Http::fake($this->fakeSuccessfulLogin());

        Nami::login(12345, 'secret');
        Nami::login(12345, 'secret');

        Http::assertSentCount(2);
    }

    public function test_it_fakes_login(): void
    {
        Auth::fake();
        Auth::success(12345, 'secret');
        Auth::assertNotLoggedIn();

        Nami::login(12345, 'secret');

        Auth::assertLoggedInWith(12345, 'secret');
        Auth::assertLoggedIn();
        Auth::assertNotLoggedInWith(12345, 'wrong');
        Http::assertSentCount(0);
    }

    public function test_it_fakes_failed_login(): void
    {
        Auth::fake();
        Auth::failed(12345, 'wrong');
        $this->expectException(LoginException::class);

        Nami::login(12345, 'wrong');

        Http::assertSentCount(0);
        Auth::assertNotLoggedIn();
    }

    public function test_it_fakes_login_state(): void
    {
        Auth::fake();
        Auth::success(12345, 'secret');
        $this->assertFalse(Nami::isLoggedIn());

        Nami::login(12345, 'secret');

        $this->assertTrue(Nami::isLoggedIn());
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
