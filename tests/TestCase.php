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
