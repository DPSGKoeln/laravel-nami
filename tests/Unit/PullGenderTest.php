<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Zoomyboy\LaravelNami\Group;
use Zoomyboy\LaravelNami\LoginException;
use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\NamiServiceProvider;
use Zoomyboy\LaravelNami\Tests\TestCase;

class PullGenderTest extends TestCase
{

    public function test_get_all_genders(): void
    {
        Http::fake([
            'https://nami.dpsg.de/ica/rest/baseadmin/geschlecht' => Http::response($this->fakeJson('genders.json'), 200)
        ]);

        $genders = $this->login()->genders();

        $this->assertEquals([
            19 => 'Männlich',
            20 => 'Weiblich'
        ], $genders->pluck('name', 'id')->toArray());

        Http::assertSentCount(1);
    }

}
