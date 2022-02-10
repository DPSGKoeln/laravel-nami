<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Zoomyboy\LaravelNami\LoginException;
use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\Tests\TestCase;

class LoginTest extends TestCase
{

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function test_first_successful_login()
    {
        Http::fake($this->login());
        $this->setCredentials();
        Nami::login();

        Http::assertSent(function($request) {
            return $request->url() == 'https://nami.dpsg.de/ica/pages/login.jsp';
        });
        Http::assertSent(function($request) {
            return $request->url() == 'https://nami.dpsg.de/ica/rest/nami/auth/manual/sessionStartup'
                && $request['username'] == '11223' && $request['password'] == 'secret' && $request['redirectTo'] == './app.jsp' && $request['Login'] == 'API';
        });
        Http::assertSentCount(2);
    }

    public function test_first_login_fails_because_of_bruteforce_protection()
    {
        Http::fake([
            'https://nami.dpsg.de/ica/pages/login.jsp' => Http::response('<html></html>', 200),
            'https://nami.dpsg.de/ica/rest/nami/auth/manual/sessionStartup' => Http::response($this->bruteJson, 200)
        ]);

        $this->setCredentials();

        try {
            Nami::login();
        } catch(LoginException $e) {
            $this->assertEquals(LoginException::TOO_MANY_FAILED_LOGINS, $e->reason);
        }
    }

    public function test_login_once_on_second_login()
    {
        Http::fake([
            'https://nami.dpsg.de/ica/pages/login.jsp' => Http::response('<html></html>', 200),
            'https://nami.dpsg.de/ica/rest/nami/auth/manual/sessionStartup' => Http::response($this->successJson, 200)
        ]);

        $this->setCredentials();

        Nami::login();
        Nami::login();

        Http::assertSent(function($request) {
            return $request->url() == 'https://nami.dpsg.de/ica/pages/login.jsp';
        });
        Http::assertSent(function($request) {
            return $request->url() == 'https://nami.dpsg.de/ica/rest/nami/auth/manual/sessionStartup'
                && $request['username'] == '11223' && $request['password'] == 'secret' && $request['redirectTo'] == './app.jsp' && $request['Login'] == 'API';
        });
        Http::assertSentCount(2);
    }

    public function test_login_check()
    {
        Http::fake([
            'https://nami.dpsg.de/ica/pages/login.jsp' => Http::response('<html></html>', 200),
            'https://nami.dpsg.de/ica/rest/nami/auth/manual/sessionStartup' => Http::sequence()->push($this->wrongCredentialsJson, 200)
        ]);

        $this->setCredentials();

        try {
            Nami::login();
        } catch(LoginException $e) {
            $this->assertEquals(LoginException::WRONG_CREDENTIALS, $e->reason);
        }

        Http::assertSentCount(2);
    }
}
