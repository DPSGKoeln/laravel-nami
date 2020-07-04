<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Zoomyboy\LaravelNami\NamiServiceProvider;
use Zoomyboy\LaravelNami\LoginException;
use Zoomyboy\LaravelNami\Group;

class PullActivitiesTest extends TestCase
{

    public $groupsResponse = '{"success":true,"data":[{"descriptor":"Group","name":"","representedClass":"de.iconcept.nami.entity.org.Gruppierung","id":103}],"responseType":"OK"}';

    public function test_get_all_activities() {
        Http::fake(array_merge($this->login(),[
            'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root' => Http::response($this->groupsResponse, 200),
            'https://nami.dpsg.de/ica/rest/nami/taetigkeitaufgruppierung/filtered/gruppierung/gruppierung/103' => Http::response($this->fakeJson('activities.json'), 200)
        ]));

        $this->setCredentials();

        Nami::login();

        $this->assertSame([
            [ 'name' => 'Ac1', 'id' => 4 ],
            [ 'name' => 'Ac2', 'id' => 3 ]
        ], Nami::group(103)->activities()->toArray());

        Http::assertSentCount(4);
    }

    public function test_get_all_subactivities() {
        Http::fake(array_merge($this->login(),[
            'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root' => Http::response($this->groupsResponse, 200),
            'https://nami.dpsg.de/ica/rest/nami/taetigkeitaufgruppierung/filtered/gruppierung/gruppierung/103' => Http::response($this->fakeJson('activities.json'), 200),
            'https://nami.dpsg.de/ica/rest/nami/untergliederungauftaetigkeit/filtered/untergliederung/taetigkeit/4' => Http::response($this->fakeJson('subactivities-4.json'), 200)
        ]));

        $this->setCredentials();

        Nami::login();

        $this->assertSame([
            [ 'name' => 'Biber', 'id' => 40 ],
            [ 'name' => 'Wölfling', 'id' => 30 ]
        ], Nami::group(103)->activities()->first()->subactivities()->toArray());
        
        Http::assertSentCount(5);
    }

}
