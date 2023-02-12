<?php

namespace Zoomyboy\LaravelNami\Tests\Unit\Api;

use Zoomyboy\LaravelNami\Data\Member;
use Zoomyboy\LaravelNami\Fakes\MemberFake;
use Zoomyboy\LaravelNami\Tests\TestCase;

class PutMemberTest extends TestCase
{
    public function testPushASingleMember(): void
    {
        app(MemberFake::class)->createsSuccessfully(103, 16);
        $member = Member::factory()->toMember([
            'firstname' => 'Max',
            'lastname' => 'Nach1',
            'nickname' => 'spitz1',
            'groupId' => 103,
            'genderId' => 17,
            'confessionId' => 33,
            'joinedAt' => '2021-02-02 00:00:00',
            'birthday' => '2021-02-02 00:00:00',
            'email' => 'aa@b.de',
            'countryId' => 78,
            'regionId' => 11,
            'nationalityId' => 12,
            'beitragsartId' => null,
            'emailParents' => 'a@b.de',
            'location' => 'SG',
            'zip' => 55555,
            'address' => 'Maxstr 5',
            'fax' => '+49 111',
            'mainPhone' => '+49 222',
            'mobilePhone' => '+49 333',
            'workPhone' => '+49 3333',
            'keepdata' => true,
            'sendNewspaper' => true,
            'otherCountry' => 'Land',
            'furtherAddress' => 'Name',
            'version' => 40,
        ]);
        $response = $this->login()->putMember($member, 78, 79);

        $this->assertEquals(16, $response);

        app(MemberFake::class)->assertCreated(103, [
            'spitzname' => 'spitz1',
            'vorname' => 'Max',
            'nachname' => 'Nach1',
            'geschlechtId' => 17,
            'email' => 'aa@b.de',
            'beitragsartId' => null,
            'geburtsDatum' => '2021-02-02 00:00:00',
            'konfessionId' => 33,
            'landId' => 78,
            'regionId' => 11,
            'staatsangehoerigkeitId' => 12,
            'ersteTaetigkeitId' => 78,
            'ersteUntergliederungId' => 79,
            'emailVertretungsberechtigter' => 'a@b.de',
            'ort' => 'SG',
            'plz' => '55555',
            'strasse' => 'Maxstr 5',
            'telefax' => '+49 111',
            'telefon1' => '+49 222',
            'telefon2' => '+49 333',
            'telefon3' => '+49 3333',
            'wiederverwendenFlag' => true,
            'zeitschriftenversand' => true,
            'staatsangehoerigkeitText' => 'Land',
            'nameZusatz' => 'Name',
            'version' => 40,
        ]);
    }

    public function testUpdateAMemberWithForeignAttributes(): void
    {
        app(MemberFake::class)
            ->updatesSuccessfully(103, 16)
            ->shows(103, 16);
        $member = Member::factory()->toMember([
            'firstname' => 'Max',
            'lastname' => 'Nach1',
            'nickname' => 'spitz1',
            'groupId' => 103,
            'genderId' => 17,
            'confessionId' => 33,
            'joinedAt' => '2021-02-02 00:00:00',
            'birthday' => '2021-02-02 00:00:00',
            'email' => 'aa@b.de',
            'countryId' => 78,
            'keepdata' => false,
            'sendNewspaper' => false,
            'regionId' => 11,
            'nationalityId' => 12,
            'beitragsartId' => null,
            'id' => 16,
        ]);
        $response = $this->login()->putMember($member);

        $this->assertEquals(16, $response);

        app(MemberFake::class)->assertUpdated(103, 16, [
            'spitzname' => 'spitz1',
            'vorname' => 'Max',
            'nachname' => 'Nach1',
            'geschlechtId' => 17,
            'email' => 'aa@b.de',
            'beitragsartId' => null,
            'geburtsDatum' => '2021-02-02 00:00:00',
            'konfessionId' => 33,
            'landId' => 78,
            'wiederverwendenFlag' => false,
            'zeitschriftenversand' => false,
            'regionId' => 11,
            'staatsangehoerigkeitId' => 12,
        ]);
    }

    public function testItMergesKontoverbindung(): void
    {
        app(MemberFake::class)
            ->updatesSuccessfully(103, 16)
            ->shows(103, 16, [
                'foreign' => 'fff',
                'kontoverbindung' => ['a' => 'b'],
            ]);
        $response = $this->login()->putMember(Member::factory()->inNami(103, 16)->toMember());

        $this->assertEquals(16, $response);

        app(MemberFake::class)->assertUpdated(103, 16, [
            'kontoverbindung' => '{"a":"b"}',
            'foreign' => 'fff',
        ]);
    }

    public function testGenderCanBeNull(): void
    {
        app(MemberFake::class)
            ->updatesSuccessfully(103, 16)
            ->shows(103, 16, [
                'foreign' => 'fff',
                'kontoverbindung' => ['a' => 'b'],
            ]);
        $response = $this->login()->putMember(Member::factory()->inNami(103, 16)->toMember(['genderId' => null, 'regionId' => null]));

        $this->assertEquals(16, $response);

        app(MemberFake::class)->assertUpdated(103, 16, [
            'geschlechtId' => 23,
            'regionId' => 23,
        ]);
    }
}
