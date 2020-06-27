<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Zoomyboy\LaravelNami\NamiServiceProvider;
use Zoomyboy\LaravelNami\LoginException;

class Login extends TestCase
{

    public $successJson = '{"servicePrefix":null,"methodCall":null,"response":null,"statusCode":0,"statusMessage":"","apiSessionName":"JSESSIONID","apiSessionToken":"ILBY--L4pZEjSKa39tCemens","minorNumber":2,"majorNumber":1}';
    public $bruteJson = '{"servicePrefix":null,"methodCall":null,"response":null,"statusCode":3000,"statusMessage":"Die höchste Anzahl von Login-Versuchen wurde erreicht. Ihr Konto ist für 15 Minuten gesperrt worden. Nach Ablauf dieser Zeitspanne wird ihr Zugang wieder freigegeben.","apiSessionName":"JSESSIONID","apiSessionToken":"tGlSpMMij9ruHfeiUYjO7SD2","minorNumber":0,"majorNumber":0}';
    public $wrongCredentialsJson = '{"servicePrefix":null,"methodCall":null,"response":null,"statusCode":3000,"statusMessage":"Benutzer nicht gefunden oder Passwort falsch.","apiSessionName":"JSESSIONID","apiSessionToken":"v7lrjgPBbXInJR57qJzVIJ05","minorNumber":0,"majorNumber":0}';

    protected function getPackageProviders($app)
    {
        return [ NamiServiceProvider::class ];
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function test_first_successful_login()
    {
        Http::fake([
            'https://nami.dpsg.de/ica/pages/login.jsp' => Http::response('<html></html>', 200),
            'https://nami.dpsg.de/ica/rest/nami/auth/manual/sessionStartup' => Http::response($this->successJson, 200)
        ]);

        Config::set('nami.auth.mglnr', '11223');
        Config::set('nami.auth.password', 'secret');
        Config::set('nami.auth.groupid', '55555');

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

        Config::set('nami.auth.mglnr', '11223');
        Config::set('nami.auth.password', 'secret');
        Config::set('nami.auth.groupid', '55555');

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

        Config::set('nami.auth.mglnr', '11223');
        Config::set('nami.auth.password', 'secret');
        Config::set('nami.auth.groupid', '55555');

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

        Config::set('nami.auth.mglnr', '11223');
        Config::set('nami.auth.password', 'secret');
        Config::set('nami.auth.groupid', '55555');

        try {
            Nami::login();
        } catch(LoginException $e) {
            $this->assertEquals(LoginException::WRONG_CREDENTIALS, $e->reason);
        }

        Http::assertSentCount(2);
    }

    /* 
    public function test_login_again_if_login_has_expired()
    {
        Http::fake([
            'https://nami.dpsg.de/*' => Http::sequence()
                ->push('<html></html>')
                ->push($this->successJson, 200)
                ->push($this->expiredJson, 200)
                ->push('<html></html>')
                ->push($this->successJson, 200)
                ->push('me', 200)
        ]);

        Config::set('nami.auth.mglnr', '11223');
        Config::set('nami.auth.password', 'secret');
        Config::set('nami.auth.groupid', '55555');

        Nami::login();
        Nami::me();

        Http::assertSent(function($request) {
            return $request->url() == 'https://nami.dpsg.de/ica/pages/login.jsp';
        });
        Http::assertSent(function($request) {
            return $request->url() == 'https://nami.dpsg.de/ica/rest/nami/auth/manual/sessionStartup'
                && $request['username'] == '11223' && $request['password'] == 'secret' && $request['redirectTo'] == './app.jsp' && $request['Login'] == 'API';
        });
        Http::assertSentCount(6);
    }
     */
}
