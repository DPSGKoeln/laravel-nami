<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Zoomyboy\LaravelNami\Fakes\SubactivityFake;
use Zoomyboy\LaravelNami\Group;
use Zoomyboy\LaravelNami\LoginException;
use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\NamiException;
use Zoomyboy\LaravelNami\NamiServiceProvider;
use Zoomyboy\LaravelNami\Tests\TestCase;

class PullActivitiesTest extends TestCase
{

    public string $groupsResponse = '{"success":true,"data":[{"descriptor":"Group","name":"","representedClass":"de.iconcept.nami.entity.org.Gruppierung","id":103}],"responseType":"OK"}';

    public function test_get_all_activities(): void
    {
        Http::fake([
            'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root' => Http::response($this->groupsResponse, 200),
            'https://nami.dpsg.de/ica/rest/nami/taetigkeitaufgruppierung/filtered/gruppierung/gruppierung/103' => Http::response($this->fakeJson('activities.json'), 200)
        ]);

        $activities = $this->login()->group(103)->activities();

        $this->assertSame([
            [ 'name' => 'Ac1', 'id' => 4 ],
            [ 'name' => 'Ac2', 'id' => 3 ]
        ], $activities->toArray());
        Http::assertSentCount(2);
    }

    public function test_get_all_subactivities(): void
    {
        Http::fake([
            'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root' => Http::response($this->groupsResponse, 200),
            'https://nami.dpsg.de/ica/rest/nami/taetigkeitaufgruppierung/filtered/gruppierung/gruppierung/103' => Http::response($this->fakeJson('activities.json'), 200),
            'https://nami.dpsg.de/ica/rest/nami/untergliederungauftaetigkeit/filtered/untergliederung/taetigkeit/4' => Http::response($this->fakeJson('subactivities-4.json'), 200)
        ]);

        $subactivities = $this->login()->group(103)->activities()->first()->subactivities();

        $this->assertSame([
            [ 'name' => 'Biber', 'id' => 40 ],
            [ 'name' => 'Wölfling', 'id' => 30 ]
        ], $subactivities->toArray());
        Http::assertSentCount(3);
    }

    public function test_throw_error_when_subactivities_request_fails(): void
    {
        $this->expectException(NamiException::class);
        app(SubactivityFake::class)->fetchFailed(4, 'sorry dude');
        Http::fake([
            'https://nami.dpsg.de/ica/rest/nami/untergliederungauftaetigkeit/filtered/untergliederung/taetigkeit/4' => Http::response($this->fakeJson('subactivities-4.json'), 200)
        ]);

        $subactivities = $this->login()->subactivitiesOf(4);

        $this->assertSame([
            [ 'name' => 'Biber', 'id' => 40 ],
            [ 'name' => 'Wölfling', 'id' => 30 ]
        ], $subactivities->toArray());
        Http::assertSentCount(3);
    }

}
