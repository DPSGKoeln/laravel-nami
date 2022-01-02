<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Zoomyboy\LaravelNami\Member;
use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\Tests\TestCase;

class PullMembershipsTest extends TestCase
{
    public $groupsResponse = '{"success":true,"data":[{"descriptor":"Group","name":"","representedClass":"de.iconcept.nami.entity.org.Gruppierung","id":103}],"responseType":"OK"}';
    public $unauthorizedResponse = '';

    public function errorProvider() {
        return [
            'unauth' => ['{"success":false,"data":null,"responseType":"EXCEPTION","message":"Access denied - no right for requested operation","title":"Exception"}'],
            'noright' => ['{"success":false,"responseType":"EXCEPTION","message":"Sicherheitsverletzung: Zugriff auf Rechte Recht (n:2001002 o:2) fehlgeschlagen"}']
        ];
    }

    public function dataProvider() {
        return [
            'id' => ['id', [68, 69]],
            'group_id' => ['group_id', [103,104]],
            'activity_id' => ['activity_id', [33, 34]],
            'subactivity_id' => ['subactivity_id', [55, null]],
            'starts_at' => ['starts_at', ['2017-02-11', '2017-11-12']],
            'ends_at' => ['ends_at', ['2017-03-11', null]],
            'activity_name' => ['activity_name', ['ReferentIn', 'Wö']],
            'subactivity_name' => ['subactivity_name', ['Pfadfinder', null]],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function test_get_all_memberships_of_a_member($key, $values) {
        Http::fake(array_merge($this->login(), [
            'https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/16/flist' => Http::response($this->fakeJson('membership-overview.json'), 200),
            'https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/16/68' => Http::response($this->fakeJson('membership-68.json'), 200),
            'https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/16/69' => Http::response($this->fakeJson('membership-69.json'), 200)
        ]));

        $this->setCredentials();

        Nami::login();
        $member = new Member(['id' => 16]);

        $memberships = $member->memberships();

        foreach ($memberships as $i => $m) {
            $this->assertSame($values[$i], $m->toArray()[$key]);
        }

        Http::assertSentCount(5);
    }

    /**
     * @dataProvider errorProvider
     */
    public function test_it_gets_no_memberships_with_no_rights($error) {
        Http::fake(array_merge($this->login(), [
            'https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/16/flist' => Http::response($error, 200)
        ]));

        $this->setCredentials();

        Nami::login();
        $member = new Member(['id' => 16]);

        $memberships = $member->memberships();
        $this->assertSame([], $member->memberships()->toArray());

        Http::assertSentCount(4);
    }
}
