<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Zoomyboy\LaravelNami\NamiServiceProvider;
use Zoomyboy\LaravelNami\LoginException;
use Zoomyboy\LaravelNami\Group;
use Zoomyboy\LaravelNami\Member;
use Illuminate\Database\Eloquent\Model;

class PushMemberTest extends TestCase
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
            'joined_at' => '2021-02-02T00:00:00',
            'birthday' => '2021-02-02',
            'id' => 16,
        ], [
            'firstname' => 'Jane',
            'lastname' => 'Nach2',
            'nickname' => null,
            'group_id' => 103,
            'gender_id' => null,
            'joined_at' => '2021-02-02T00:00:00',
            'birthday' => '2021-02-02',
            'id' => 17,
        ]
    ];

    public function dataProvider() {
        return [
            'firstname' => ['vorname', ['Max', 'Jane']],
            'lastname' => ['nachname', ['Nach1', 'Nach2']],
            'nickname' => ['spitzname', ['spitz1', '']],
            'gender_id' => ['geschlechtId', [17, 23]],
            'group_id' => ['gruppierungId', [103, 103]],
            'nami_id' => ['id', [16, 17]],
            /* 
            'other_country' => ['other_country', ['deutsch', null]],
            'address' => ['address', ['straße 1', 'straße 2']],
            'further_address' => ['further_address', ['addrz', null]],
            'zip' => ['zip', ['12345', '5555']],
            'location' => ['location', ['SG', 'Köln']],
            'main_phone' => ['main_phone', ['+49888', '+49668']],
            'mobile_phone' => ['mobile_phone', ['+49176', '+49172']],
            'work_phone' => ['work_phone', ['+11111', '+22222']],
            'fax' => ['fax', ['+55111', '+55222']],
            'email' => ['email', ['test@example.com', 'test2@example.com']],
            'email_parents' => ['email_parents', ['testp@example.com', 'test2p@example.com']],
            'gender_id' => ['gender_id', [19, null]],
            'nationality_id' => ['nationality_id', [1054, null]],
            'confession_id' => ['confession_id', [1, null]],
            'birthday' => ['birthday', ['1991-06-20', '1984-01-17']],
            'joined_at' => ['joined_at', ['2005-05-01', null]],
            'group_id' => ['group_id', [103, 103]],
             */
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function test_push_a_single_member($key, $values) {
        Http::fake(array_merge($this->login(), [
            'https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/103/16' => Http::response('{"id": 16}', 200),
            'https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/103/17' => Http::response('{"id": 17}', 200),
        ]));

        $this->setCredentials();
        Nami::login();

        Nami::putMember($this->attributes[0]);
        Nami::putMember($this->attributes[1]);

        Http::assertSent(function($request) use ($key, $values) {
            return $request->url() == 'https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/103/16'
                && $request[$key] === $values[0]
                && $request->method() == 'PUT';
        });

        Http::assertSent(function($request) use ($key, $values) {
            return $request->url() == 'https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/103/17'
                && $request[$key] === $values[1]
                && $request->method() == 'PUT';
        });

        Http::assertSentCount(4);
    }

}
