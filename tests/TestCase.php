<?php

namespace Zoomyboy\LaravelNami\Tests;

use Spatie\LaravelData\LaravelDataServiceProvider;
use Worksome\RequestFactories\RequestFactoriesServiceProvider;
use Zoomyboy\LaravelNami\Api;
use Zoomyboy\LaravelNami\Authentication\Auth;
use Zoomyboy\LaravelNami\Authentication\Authenticator;
use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\Providers\NamiServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->setupCookies();
    }

    protected function getPackageProviders($app)
    {
        return [RequestFactoriesServiceProvider::class, LaravelDataServiceProvider::class, NamiServiceProvider::class];
    }

    public function getAnnotations(): array
    {
        return [];
    }

    public function fakeJson(string $file, array $data = []): string
    {
        ob_start();
        include __DIR__.'/json/'.$file;

        return ob_get_clean();
    }

    public function login(): Api
    {
        Auth::fake();
        Auth::success(12345, 'secret');

        return Nami::login(12345, 'secret');
    }

    public function loginWithWrongCredentials(): Api
    {
        Auth::fake();

        return Nami::login(12345, 'wrong');
    }

    protected function clearCookies(): void
    {
        foreach (glob(__DIR__.'/../.cookies_test/*') as $file) {
            unlink($file);
        }
    }

    private function setupCookies(): void
    {
        Authenticator::setPath(__DIR__.'/../.cookies_test');
        $this->clearCookies();
    }
}
