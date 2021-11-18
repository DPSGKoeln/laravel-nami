<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Zoomyboy\LaravelNami\Group;
use Zoomyboy\LaravelNami\LoginException;
use Zoomyboy\LaravelNami\Member;
use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\NamiServiceProvider;
use Zoomyboy\LaravelNami\Tests\TestCase;

class SearchTest extends TestCase
{
    public $groupsResponse = '{"success":true,"data":[{"descriptor":"Group","name":"","representedClass":"de.iconcept.nami.entity.org.Gruppierung","id":103}],"responseType":"OK"}';
    public $unauthorizedResponse = '{"success":false,"data":null,"responseType":"EXCEPTION","message":"Access denied - no right for requested operation","title":"Exception"}';

    public $attributes = [
        [
            'firstname' => 'Max',
            'lastname' => 'Nach1',
            'group_id' => 103,
            'nickname' => 'spitz1',
            'gender_id' => 17,
            'id' => 16,
        ], [
            'firstname' => 'Jane',
            'lastname' => 'Nach2',
            'nickname' => null,
            'group_id' => 103,
            'gender_id' => null,
            'id' => 17,
        ]
    ];

    public function dataProvider() {
        return [
            'firstname' => ['vorname', ['Max', 'Jane']],
        ];
    }

    public function test_find_a_member_by_mglnr() {
        Http::fake(array_merge($this->login(), [
            $this->url(['mitgliedsNummber' => 150]) => Http::response($this->fakeJson('searchResponse.json'), 200),
        ]));

        $this->setCredentials();
        Nami::login();

        $member = Nami::findNr(150);
        $this->assertEquals('Philipp', $member->firstname);

        Http::assertSent(function($request) {
            return $request->url() == $this->url(['mitgliedsNummber' => 150])
                && $request->method() == 'GET';
        });

        Http::assertSentCount(3);
    }

    private function url($payload) {
        $payload = rawurlencode(json_encode($payload));
        return "https://nami.dpsg.de/ica/rest/nami/search-multi/result-list?searchedValues={$payload}&page=1&start=0&limit=100";
    }

}
