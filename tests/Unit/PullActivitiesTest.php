<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Zoomyboy\LaravelNami\Fakes\SubactivityFake;
use Zoomyboy\LaravelNami\NamiException;
use Zoomyboy\LaravelNami\Tests\TestCase;

class PullActivitiesTest extends TestCase
{
    public string $groupsResponse = '{"success":true,"data":[{"descriptor":"Group","name":"","representedClass":"de.iconcept.nami.entity.org.Gruppierung","id":103}],"responseType":"OK"}';

    public function testGetAllActivities(): void
    {
        Http::fake([
            'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root' => Http::response($this->groupsResponse, 200),
            'https://nami.dpsg.de/ica/rest/nami/taetigkeitaufgruppierung/filtered/gruppierung/gruppierung/103' => Http::response($this->fakeJson('activities.json'), 200),
        ]);

        $activities = $this->login()->group(103)->activities();

        $this->assertSame([
            ['name' => 'Ac1', 'id' => 4],
            ['name' => 'Ac2', 'id' => 3],
        ], $activities->toArray());
        Http::assertSentCount(2);
    }

    public function testGetAllSubactivities(): void
    {
        Http::fake([
            'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root' => Http::response($this->groupsResponse, 200),
            'https://nami.dpsg.de/ica/rest/nami/taetigkeitaufgruppierung/filtered/gruppierung/gruppierung/103' => Http::response($this->fakeJson('activities.json'), 200),
            'https://nami.dpsg.de/ica/rest/nami/untergliederungauftaetigkeit/filtered/untergliederung/taetigkeit/4' => Http::response($this->fakeJson('subactivities-4.json'), 200),
        ]);

        $subactivities = $this->login()->group(103)->activities()->first()->subactivities();

        $this->assertSame([
            ['name' => 'Biber', 'id' => 40],
            ['name' => 'Wölfling', 'id' => 30],
        ], $subactivities->toArray());
        Http::assertSentCount(3);
    }

    public function testThrowErrorWhenSubactivitiesRequestFails(): void
    {
        $this->expectException(NamiException::class);
        app(SubactivityFake::class)->fetchFails(4, 'sorry dude');

        $subactivities = $this->login()->subactivitiesOf(4);
    }

    public function testContinueIfSubactivitiesRequestReturnsHtml(): void
    {
        app(SubactivityFake::class)->fetchFailsWithoutJson(4);

        $subactivities = $this->login()->subactivitiesOf(4);

        $this->assertCount(0, $subactivities);
    }
}
