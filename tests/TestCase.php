<?php

namespace Zoomyboy\LaravelNami\Tests;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Zoomyboy\LaravelNami\Api;
use Zoomyboy\LaravelNami\Cookies\Cookie;
use Zoomyboy\LaravelNami\Cookies\FakeCookie;
use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\Providers\NamiServiceProvider;
use Zoomyboy\LaravelNami\Tests\Stub\Member;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public $successJson = '{"servicePrefix":null,"methodCall":null,"response":null,"statusCode":0,"statusMessage":"","apiSessionName":"JSESSIONID","apiSessionToken":"ILBY--L4pZEjSKa39tCemens","minorNumber":2,"majorNumber":1}';
    public $bruteJson = '{"servicePrefix":null,"methodCall":null,"response":null,"statusCode":3000,"statusMessage":"Die höchste Anzahl von Login-Versuchen wurde erreicht. Ihr Konto ist für 15 Minuten gesperrt worden. Nach Ablauf dieser Zeitspanne wird ihr Zugang wieder freigegeben.","apiSessionName":"JSESSIONID","apiSessionToken":"tGlSpMMij9ruHfeiUYjO7SD2","minorNumber":0,"majorNumber":0}';
    public $wrongCredentialsJson = '{"servicePrefix":null,"methodCall":null,"response":null,"statusCode":3000,"statusMessage":"Benutzer nicht gefunden oder Passwort falsch.","apiSessionName":"JSESSIONID","apiSessionToken":"v7lrjgPBbXInJR57qJzVIJ05","minorNumber":0,"majorNumber":0}';

    public function setUp(): void {
        parent::setUp();

        $this->clearCookies();
    }

    protected function getPackageProviders($app)
    {
        return [ NamiServiceProvider::class ];
    }

    public function getAnnotations(): array {
        return [];
    }

    public function fakeJson(string $file, array $data = []): string {
        ob_start();
        include(__DIR__.'/json/'.$file);
        return ob_get_clean();
    }

    public function login(): Api
    {
        touch (__DIR__.'/../.cookies/'.time().'.txt');

        return Nami::login(123, 'secret');
    }

    private function clearCookies(): void
    {
        foreach (glob(__DIR__.'/../.cookies/*') as $file) {
            unlink($file);
        }
    }

}
