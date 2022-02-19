<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Zoomyboy\LaravelNami\Fakes\MemberFake;
use Zoomyboy\LaravelNami\Group;
use Zoomyboy\LaravelNami\LoginException;
use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\NamiException;
use Zoomyboy\LaravelNami\NamiServiceProvider;
use Zoomyboy\LaravelNami\Tests\TestCase;

class PullMemberTest extends TestCase
{
    public string $groupsResponse = '{"success":true,"data":[{"descriptor":"Group","name":"","representedClass":"de.iconcept.nami.entity.org.Gruppierung","id":103}],"responseType":"OK"}';
    public string $unauthorizedResponse = '{"success":false,"data":null,"responseType":"EXCEPTION","message":"Access denied - no right for requested operation","title":"Exception"}';

    public function dataProvider(): array {
        return [
            'firstname' => [ ['vorname' => 'Max'], ['firstname' => 'Max' ] ],
            'lastname' => [ ['nachname' => 'Nach'], ['lastname' => 'Nach' ] ],
            'nickname' => [ ['spitzname' => 'spitz1'], ['nickname' => 'spitz1'] ],
            'nicknameEmpty' => [ ['spitzname' => null], ['nickname' => null] ],
            'other_country' => [ ['staatsangehoerigkeitText' => 'deutsch'], ['other_country' => 'deutsch'] ],
            'other_countryEmpty' => [ ['staatsangehoerigkeitText' => ''], ['other_country' => null] ],
            'address' => [ ['strasse' => 'Straße 1'], ['address' => 'Straße 1'] ],
            'further_address' => [ ['nameZusatz' => 'addrz'], ['further_address' => 'addrz'] ],
            'further_addressEmpty' => [ ['nameZusatz' => ''], ['further_address' => null] ],
            'zip' => [ ['plz' => '12345'], ['zip' => '12345'] ],
            'location' => [ ['ort' => 'Köln'], ['location' => 'Köln'] ],
            'main_phone' => [ ['telefon1' => '+49888'], ['main_phone' => '+49888'] ],
            'mobile_phone' => [ ['telefon2' => '+49176'], ['mobile_phone' => '+49176'] ],
            'work_phone' => [ ['telefon3' => '+49177'], ['work_phone' => '+49177'] ],
            'fax' => [ ['telefax' => '+55111'], ['fax' => '+55111'] ],
            'email' => [ ['email' => 'a@b.de'], ['email' => 'a@b.de'] ],
            'email_parents' => [ ['emailVertretungsberechtigter' => 'v@b.de'], ['email_parents' => 'v@b.de'] ],
            'gender_id' => [ ['geschlechtId' => 19], ['gender_id' => 19] ],
            'gender_idEmpty' => [ ['geschlechtId' => 23], ['gender_id' => null] ],
            'nationality_id' => [ ['staatsangehoerigkeitId' => 1054], ['nationality_id' => 1054] ],
            'nationality_idEmpty' => [ ['staatsangehoerigkeitId' => null], ['nationality_id' => null] ],
            'confession_id' => [ ['konfessionId' => 1], ['confession_id' => 1] ],
            'confession_idEmpty' => [ ['konfessionId' => null], ['confession_id' => null] ],
            'birthday' => [ ['geburtsDatum' => "1991-06-20 00:00:00"], ['birthday' => "1991-06-20"] ],
            'joined_at' => [ ['eintrittsdatum' => "2005-05-01 00:00:00"], ['joined_at' => "2005-05-01"] ],
            'group_id' => [ ['gruppierungId' => 103], ['group_id' => 103] ],
            'mitgliedsnr' => [ ['mitgliedsNummer' => 12345], ['mitgliedsnr' => 12345] ],
            'mitgliedsnrEmpty' => [ ['mitgliedsNummer' => null], ['mitgliedsnr' => null] ],
            'updated_at' => [ ['lastUpdated' => "2020-06-28 02:15:24"], ['updated_at' => '2020-06-28 02:15:24'] ],
            'send_newspaper' => [ ['zeitschriftenversand' => true], ['send_newspaper' => true] ],
            'region_id' => [ ['regionId' => 10], ['region_id' => 10] ],
            'region_idEmpty' => [ ['regionId' => null], ['region_id' => null] ],
            'country_id' => [ ['landId' => 100], ['country_id' => 100] ],
            'fee_id' => [ ['beitragsartId' => 1], ['fee_id' => 1] ],
        ];
    }

    public function overviewDataProvider(): array {
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
            'joined_at' => ['joined_at', ['2005-05-01', null]],
            'group_id' => ['group_id', [103, 103]],
            'mitgliedsnr' => ['mitgliedsnr', [12345, null]],
            'updated_at' => ['updated_at', ['2020-06-28 02:15:24', '2015-02-03 15:20:07']]
        ];
    }

    public function relationProvider(): array {
        return [
            'firstname' => ['firstname', ['Max', 'Jane']],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function test_get_a_single_member(array $input, array $check): void
    {
        Http::fake([
            'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root' => Http::response($this->groupsResponse, 200),
            'https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/103/16' => Http::response($this->fakeJson('member-16.json', ['data' => $input]), 200),
        ]);

        $group = $this->login()->group(103);

        foreach ($check as $key => $value) {
            $this->assertSame($value, $group->member(16)->toArray()[$key]);
        }

        Http::assertSentCount(2);
    }

    /**
     * @dataProvider dataProvider
     */
    public function test_get_attribute_of_member_collection(array $input, array $check): void
    {
        Http::fake([
            'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root' => Http::response($this->groupsResponse, 200),
            'https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/103/flist' => Http::response($this->fakeJson('member_overview.json'), 200),
            'https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/103/16' => Http::response($this->fakeJson('member-16.json', ['data' => $input]), 200),
        ]);

        $member = $this->login()->group(103)->members()->first();

        foreach ($check as $key => $value) {
            $this->assertSame($value, $member->toArray()[$key]);
        }
        Http::assertSentCount(3);
    }

    /**
     * @dataProvider overviewDataProvider
     */
    public function test_get_attribute_of_member_overview(string $key, array $values): void
    {
        Http::fake([
            'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root' => Http::response($this->groupsResponse, 200),
            'https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/103/flist' => Http::response($this->fakeJson('member_overview.json'), 200)
        ]);

        $members = $this->login()->group(103)->memberOverview();
        foreach ($members as $i => $m) {
            $this->assertSame($values[$i], $m->toArray()[$key]);
        }

        Http::assertSentCount(2);
    }

    /**
     * @dataProvider relationProvider
     */
    public function test_set_relations(string $key, array $values): void
    {
        Http::fake([
            'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root' => Http::response($this->groupsResponse, 200),
            'https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/103/flist' => Http::response($this->fakeJson('member_overview.json'), 200),
            'https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/103/16' => Http::response($this->fakeJson('member-16.json'), 200),
            'https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/103/17' => Http::response($this->fakeJson('member-17.json'), 200)
        ]);

        $members = $this->login()->group(103)->members();

        $this->assertSame([
            16 => $values[0],
            17 => $values[1]
        ], $members->pluck($key, 'id')->toArray());

        Http::assertSentCount(4);
    }

    /**
     * @dataProvider overviewDataProvider
     */
    public function test_get_a_member_from_overview_with_no_rights(string $key, array $values): void
    {
        Http::fake([
            'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root' => Http::response($this->groupsResponse, 200),
            'https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/103/16' => Http::response($this->unauthorizedResponse, 200),
            'https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/103/flist' => Http::response($this->fakeJson('member_overview.json'), 200)
        ]);

        $member = $this->login()->group(103)->member(16);

        $this->assertSame($values[0], $member->toArray()[$key]);

        Http::assertSentCount(3);
    }

    public function test_member_fetch_can_fail(): void
    {
        $this->expectException(NamiException::class);
        app(MemberFake::class)->fetchFails(103, 16);

        $this->login()->member(103, 16);
    }

}
