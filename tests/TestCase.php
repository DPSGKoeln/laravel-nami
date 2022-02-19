<?php

namespace Zoomyboy\LaravelNami\Tests;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Zoomyboy\LaravelNami\Api;
use Zoomyboy\LaravelNami\Authentication\Authenticator;
use Zoomyboy\LaravelNami\Cookies\Cookie;
use Zoomyboy\LaravelNami\Cookies\FakeCookie;
use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\Providers\NamiServiceProvider;
use Zoomyboy\LaravelNami\Tests\Stub\Member;

class TestCase extends \Orchestra\Testbench\TestCase
{

    public function setUp(): void {
        parent::setUp();

        $this->setupCookies();
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
        touch (__DIR__.'/../.cookies_test/'.time().'.txt');

        return Nami::login(123, 'secret');
    }

    private function setupCookies(): void
    {
        Authenticator::setPath(__DIR__.'/../.cookies_test');

        foreach (glob(__DIR__.'/../.cookies_test/*') as $file) {
            unlink($file);
        }
    }

}
