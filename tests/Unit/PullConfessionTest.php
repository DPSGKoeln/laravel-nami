<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Zoomyboy\LaravelNami\Group;
use Zoomyboy\LaravelNami\LoginException;
use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\NamiServiceProvider;
use Zoomyboy\LaravelNami\Tests\TestCase;

class PullConfessionTest extends TestCase
{

    public function test_get_all_confessions(): void
    {
        Http::fake([
            'https://nami.dpsg.de/ica/rest/baseadmin/konfession' => Http::response($this->fakeJson('confession.json'), 200)
        ]);

        $confessions = $this->login()->confessions();

        $this->assertEquals([
            1 => 'römisch-katholisch'
        ], $confessions->pluck('name', 'id')->toArray());

        Http::assertSentCount(1);
    }

}
