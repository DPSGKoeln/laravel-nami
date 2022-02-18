<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Zoomyboy\LaravelNami\Group;
use Zoomyboy\LaravelNami\LoginException;
use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\NamiServiceProvider;
use Zoomyboy\LaravelNami\Tests\TestCase;

class PullNationalityTest extends TestCase
{

    public function test_get_all_nationalities(): void
    {
        Http::fake([
            'https://nami.dpsg.de/ica/rest/baseadmin/staatsangehoerigkeit' => Http::response($this->fakeJson('nationalities.json'))
        ]);

        $nationalities = $this->login()->nationalities();

        $this->assertEquals([
            ['name' => 'deutsch', 'id' => 1054]
        ], $nationalities->toArray());

        Http::assertSentCount(1);
    }

}
