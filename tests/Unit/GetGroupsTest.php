<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Zoomyboy\LaravelNami\Group;
use Zoomyboy\LaravelNami\LoginException;
use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\NamiServiceProvider;
use Zoomyboy\LaravelNami\Tests\TestCase;

class GetGroupsTest extends TestCase
{

    public string $groupsResponse = '{"success":true,"data":[{"descriptor":"Group","name":"","representedClass":"de.iconcept.nami.entity.org.Gruppierung","id":100}],"responseType":"OK"}';
    public string $subgroupsResponse = '{ "success": true, "data": [ { "descriptor": "Siebengebirge", "name": "", "representedClass": "de.iconcept.nami.entity.org.Gruppierung", "id": 101300 }, { "descriptor": "Sieg", "name": "", "representedClass": "de.iconcept.nami.entity.org.Gruppierung", "id": 100900 }, { "descriptor": "Sieg", "name": "", "representedClass": "de.iconcept.nami.entity.org.Gruppierung", "id": 100900 }, { "descriptor": "Voreifel", "name": "", "representedClass": "de.iconcept.nami.entity.org.Gruppierung", "id": 101000 } ], "responseType": "OK" }';
    public string $subsubgroupsResponse = '{ "success": true, "data": [ { "descriptor": "Silva", "name": "", "representedClass": "de.iconcept.nami.entity.org.Gruppierung", "id": 100105 } ], "responseType": "OK" }';

    public function test_get_all_groups(): void
    {
        Http::fake([
            'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root' => Http::response($this->groupsResponse, 200),
        ]);

        $groups = $this->login()->groups();

        $this->assertEquals([
            ['id' => 100, 'name' => 'Group', 'parent_id' => null]
        ], $groups->toArray());
        Http::assertSent(function($request) {
            return $request->url() == 'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root';
        });
        Http::assertSentCount(1);
    }

    public function test_has_group_access(): void
    {
        Http::fake([
            'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root' => Http::response($this->groupsResponse, 200),
        ]);
        $api = $this->login();

        $this->assertTrue($api->hasGroup(100));
        $this->assertFalse($api->hasGroup(10101));

        Http::assertSentCount(2);
    }

    public function test_get_subgroups_for_a_group(): void
    {
        Http::fake([
            'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root' => Http::response($this->groupsResponse, 200),
            'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/100' => Http::response($this->subgroupsResponse, 200)
        ]);

        $subgroups = $this->login()->group(100)->subgroups();

        $this->assertEquals([
            ['id' => 101300, 'parent_id' => 100, 'name' => 'Siebengebirge'],
            ['id' => 100900, 'parent_id' => 100, 'name' => 'Sieg'],
            ['id' => 100900, 'parent_id' => 100, 'name' => 'Sieg'],
            ['id' => 101000, 'parent_id' => 100, 'name' => 'Voreifel']
        ], $subgroups->toArray());
        $subgroups->each(function($group) {
            $this->assertInstanceOf(Group::class, $group);
        });
        Http::assertSent(function($request) {
            return $request->url() == 'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root';
        });
        Http::assertSent(function($request) {
            return $request->url() == 'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/100';
        });
        Http::assertSentCount(2);
    }
}
