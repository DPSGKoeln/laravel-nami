<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Zoomyboy\LaravelNami\Tests\TestCase;

class PullGenderTest extends TestCase
{
    public function testGetAllGenders(): void
    {
        Http::fake([
            'https://nami.dpsg.de/ica/rest/baseadmin/geschlecht' => Http::response($this->fakeJson('genders.json'), 200),
        ]);

        $genders = $this->login()->genders();

        $this->assertEquals([
            19 => 'Männlich',
            20 => 'Weiblich',
        ], $genders->pluck('name', 'id')->toArray());

        Http::assertSentCount(1);
    }
}
