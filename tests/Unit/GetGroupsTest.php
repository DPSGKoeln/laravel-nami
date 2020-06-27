<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Zoomyboy\LaravelNami\NamiServiceProvider;
use Zoomyboy\LaravelNami\LoginException;

class GetGroupsTest extends TestCase
{

    public $groupsResponse = '{"success":true,"data":[{"descriptor":"Group","name":"","representedClass":"de.iconcept.nami.entity.org.Gruppierung","id":100}],"responseType":"OK"}';

    public function test_get_all_groups()
    {
        Http::fake([
            'https://nami.dpsg.de/ica/pages/login.jsp' => Http::response('<html></html>', 200),
            'https://nami.dpsg.de/ica/rest/nami/auth/manual/sessionStartup' => Http::response($this->successJson, 200),
            'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root' => Http::response($this->groupsResponse, 200),
        ]);

        $this->setCredentials();

        Nami::login();
        $this->assertEquals([
            (object) ['id' => 100, 'name' => 'Group']
        ], Nami::groups()->toArray());

        Http::assertSent(function($request) {
            return $request->url() == 'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root';
        });
        Http::assertSentCount(3);
    }

    public function test_has_group_access()
    {
        Http::fake([
            'https://nami.dpsg.de/ica/pages/login.jsp' => Http::response('<html></html>', 200),
            'https://nami.dpsg.de/ica/rest/nami/auth/manual/sessionStartup' => Http::response($this->successJson, 200),
            'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root' => Http::response($this->groupsResponse, 200),
        ]);

        $this->setCredentials();

        Nami::login();
        $this->assertTrue(Nami::hasGroup(100));
        $this->assertFalse(Nami::hasGroup(10101));

        Http::assertSentCount(4);
    }
}
