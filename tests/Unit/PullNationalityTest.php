<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Zoomyboy\LaravelNami\NamiServiceProvider;
use Zoomyboy\LaravelNami\LoginException;
use Zoomyboy\LaravelNami\Group;

class PullNationalityTest extends TestCase
{

    public function test_get_all_nationalities() {
        Http::fake(array_merge($this->login(), [
            'https://nami.dpsg.de/ica/rest/baseadmin/staatsangehoerigkeit' => Http::response($this->fakeJson('nationalities.json'))
        ]));

        $this->setCredentials();

        Nami::login();

        $this->assertEquals([
            ['name' => 'deutsch', 'id' => 1054]
        ], Nami::nationalities()->toArray());

        Http::assertSentCount(3);
    }

}
