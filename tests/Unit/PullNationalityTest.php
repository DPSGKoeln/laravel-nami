<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Zoomyboy\LaravelNami\Tests\TestCase;

class PullNationalityTest extends TestCase
{
    public function testGetAllNationalities(): void
    {
        Http::fake([
            'https://nami.dpsg.de/ica/rest/baseadmin/staatsangehoerigkeit' => Http::response($this->fakeJson('nationalities.json')),
        ]);

        $nationalities = $this->login()->nationalities();

        $this->assertEquals([
            ['name' => 'deutsch', 'id' => 1054],
        ], $nationalities->toArray());

        Http::assertSentCount(1);
    }
}
