<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Zoomyboy\LaravelNami\NamiServiceProvider;
use Zoomyboy\LaravelNami\LoginException;
use Zoomyboy\LaravelNami\Group;

class PullConfessionTest extends TestCase
{

    public function test_get_all_confessions() {
        Http::fake(array_merge($this->login(), [
            'https://nami.dpsg.de/ica/rest/baseadmin/konfession' => Http::response($this->fakeJson('confession.json'), 200)
        ]));

        $this->setCredentials();

        Nami::login();

        $this->assertEquals([
            1 => 'römisch-katholisch'
        ], Nami::confessions()->pluck('name', 'id')->toArray());

        Http::assertSentCount(3);
    }

}
