<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Zoomyboy\LaravelNami\NamiServiceProvider;
use Zoomyboy\LaravelNami\LoginException;
use Zoomyboy\LaravelNami\Group;

class PullMemberTest extends TestCase
{
    public $groupsResponse = '{"success":true,"data":[{"descriptor":"Group","name":"","representedClass":"de.iconcept.nami.entity.org.Gruppierung","id":103}],"responseType":"OK"}';
    public $unauthorizedResponse = '{"success":false,"data":null,"responseType":"EXCEPTION","message":"Access denied - no right for requested operation","title":"Exception"}';

    public function dataProvider() {
        return [
            'firstname' => ['firstname', ['Max', 'Jane']],
            'lastname' => ['lastname', ['Nach1', 'Nach2']],
            'nickname' => ['nickname', ['spitz1', null]],
            'other_country' => ['other_country', ['deutsch', null]],
            'address' => ['address', ['straße 1', 'straße 2']],
            'further_address' => ['further_address', ['addrz', null]],
            'zip' => ['zip', [12345, 5555]],
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
            'joined_at' => ['joined_at', ['2005-05-01', '2003-11-17']],
        ];
    }

    public function overviewDataProvider() {
        return [
            'firstname' => ['firstname', ['Max', 'Jane']],
            'lastname' => ['lastname', ['Nach1', 'Nach2']],
            'nickname' => ['nickname', ['spitz1', null]],
            'other_country' => ['other_country', ['deutsch', null]],
            'main_phone' => ['main_phone', ['+49888', '+49668']],
            'mobile_phone' => ['mobile_phone', ['+49176', '+49172']],
            'work_phone' => ['work_phone', ['+11111', '+22222']],
            'fax' => ['fax', ['+55111', '+55222']],
            'email' => ['email', ['test@example.com', 'test2@example.com']],
            'email_parents' => ['email_parents', ['testp@example.com', 'test2p@example.com']],
            'gender_id' => ['gender_id', [19, null]],
            'birthday' => ['birthday', ['1991-06-20', '1984-01-17']],
            'joined_at' => ['joined_at', ['2005-05-01', '2003-11-17']],
        ];
    }

    public function relationProvider() {
        return [
            'firstname' => ['firstname', ['Max', 'Jane']],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function test_get_a_single_member($key, $values) {
        Http::fake(array_merge($this->login(), [
            'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root' => Http::response($this->groupsResponse, 200),
            'https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/103/16' => Http::response($this->fakeJson('member-16.json'), 200),
            'https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/103/17' => Http::response($this->fakeJson('member-17.json'), 200)
        ]));

        $this->setCredentials();

        Nami::login();

        $group = Nami::group(103);

        $this->assertEquals($values[0], $group->member(16)->{$key});
        $this->assertEquals($values[1], $group->member(17)->{$key});

        Http::assertSentCount(5);
    }

    /**
     * @dataProvider dataProvider
     */
    public function test_get_attribute_of_overview($key, $values) {
        Http::fake(array_merge($this->login(), [
            'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root' => Http::response($this->groupsResponse, 200),
            'https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/103/flist' => Http::response($this->fakeJson('member_overview.json'), 200),
            'https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/103/16' => Http::response($this->fakeJson('member-16.json'), 200),
            'https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/103/17' => Http::response($this->fakeJson('member-17.json'), 200)
        ]));

        $this->setCredentials();

        Nami::login();

        $this->assertEquals([
            16 => $values[0],
            17 => $values[1]
        ], Nami::group(103)->members()->pluck($key, 'id')->toArray());

        Http::assertSentCount(6);
    }

    /**
     * @dataProvider relationProvider
     */
    public function test_set_relations($key, $values) {
        Http::fake(array_merge($this->login(), [
            'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root' => Http::response($this->groupsResponse, 200),
            'https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/103/flist' => Http::response($this->fakeJson('member_overview.json'), 200),
            'https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/103/16' => Http::response($this->fakeJson('member-16.json'), 200),
            'https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/103/17' => Http::response($this->fakeJson('member-17.json'), 200)
        ]));

        $this->setCredentials();

        Nami::login();

        $this->assertEquals([
            16 => $values[0],
            17 => $values[1]
        ], Nami::group(103)->members()->pluck($key, 'id')->toArray());

        Http::assertSentCount(6);
    }

    /**
     * @dataProvider overviewDataProvider
     */
    public function test_get_a_member_from_overview_with_no_rights($key, $values) {
        Http::fake(array_merge($this->login(), [
            'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root' => Http::response($this->groupsResponse, 200),
            'https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/103/16' => Http::response($this->unauthorizedResponse, 200),
            'https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/103/flist' => Http::response($this->fakeJson('member_overview.json'), 200)
        ]));

        $this->setCredentials();

        Nami::login();

        $group = Nami::group(103);

        $this->assertEquals($values[0], $group->member(16)->{$key});

        Http::assertSentCount(5);
    }

}
