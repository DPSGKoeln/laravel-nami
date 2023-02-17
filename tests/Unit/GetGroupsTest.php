<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Zoomyboy\LaravelNami\Data\Group;
use Zoomyboy\LaravelNami\Tests\TestCase;

class GetGroupsTest extends TestCase
{
    public string $groupsResponse = '{"success":true,"data":[{"descriptor":"Group","name":"","representedClass":"de.iconcept.nami.entity.org.Gruppierung","id":100}],"responseType":"OK"}';
    public string $subgroupsResponse = '{ "success": true, "data": [ { "descriptor": "Siebengebirge", "name": "", "representedClass": "de.iconcept.nami.entity.org.Gruppierung", "id": 101300 }, { "descriptor": "Sieg", "name": "", "representedClass": "de.iconcept.nami.entity.org.Gruppierung", "id": 100900 }, { "descriptor": "Sieg", "name": "", "representedClass": "de.iconcept.nami.entity.org.Gruppierung", "id": 100900 }, { "descriptor": "Voreifel", "name": "", "representedClass": "de.iconcept.nami.entity.org.Gruppierung", "id": 101000 } ], "responseType": "OK" }';
    public string $subsubgroupsResponse = '{ "success": true, "data": [ { "descriptor": "Silva", "name": "", "representedClass": "de.iconcept.nami.entity.org.Gruppierung", "id": 100105 } ], "responseType": "OK" }';

    public function testGetAllGroups(): void
    {
        Http::fake([
            'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root' => Http::response($this->groupsResponse, 200),
        ]);

        $group = $this->login()->groups()->first();

        $this->assertSame(100, $group->id);
        $this->assertSame('Group', $group->name);
        $this->assertNull($group->parentId);
        Http::assertSent(function ($request) {
            return 'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root' == $request->url();
        });
        Http::assertSentCount(1);
    }

    public function testHasGroupAccess(): void
    {
        Http::fake([
            'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root' => Http::response($this->groupsResponse, 200),
        ]);
        $api = $this->login();

        $this->assertTrue($api->hasGroup(100));
        $this->assertFalse($api->hasGroup(10101));

        Http::assertSentCount(2);
    }

    public function testGetSubgroupsForAGroup(): void
    {
        Http::fake([
            'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root' => Http::response($this->groupsResponse, 200),
            'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/100' => Http::response($this->subgroupsResponse, 200),
        ]);

        $subgroups = $this->login()->group(100)->children();

        $this->assertEquals([
            ['id' => 101300, 'parentId' => 100, 'name' => 'Siebengebirge'],
            ['id' => 100900, 'parentId' => 100, 'name' => 'Sieg'],
            ['id' => 100900, 'parentId' => 100, 'name' => 'Sieg'],
            ['id' => 101000, 'parentId' => 100, 'name' => 'Voreifel'],
        ], $subgroups->toArray());
        $subgroups->each(function ($group) {
            $this->assertInstanceOf(Group::class, $group);
        });
        Http::assertSent(function ($request) {
            return 'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root' == $request->url();
        });
        Http::assertSent(function ($request) {
            return 'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/100' == $request->url();
        });
        Http::assertSentCount(2);
    }
}
