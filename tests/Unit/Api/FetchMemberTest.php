<?php

namespace Zoomyboy\LaravelNami\Tests\Unit\Member;

use Zoomyboy\LaravelNami\Fakes\MemberFake;
use Zoomyboy\LaravelNami\NamiException;
use Zoomyboy\LaravelNami\Tests\TestCase;

class FetchMemberTest extends TestCase
{
    public function testGetASingleMember(): void
    {
        app(MemberFake::class)->shows(1000, 1001, [
            'beitragsartId' => 1,
            'eintrittsdatum' => '2005-05-01 00:00:00',
            'email' => 'pille@stamm-silva.de',
            'emailVertretungsberechtigter' => 'aa@b.de',
            'geburtsDatum' => '1991-06-20 00:00:00',
            'geschlechtId' => 19,
            'konfessionId' => 1,
            'landId' => 1,
            'lastUpdated' => '2022-03-20 11:58:33',
            'nachname' => 'Lang',
            'nameZusatz' => 'zuss',
            'ort' => 'Solingen',
            'plz' => '42719',
            'regionId' => 10,
            'spitzname' => 'pille',
            'staatsangehoerigkeitId' => 1054,
            'staatsangehoerigkeitText' => 'lorem',
            'strasse' => 'Maxstr 5',
            'telefax' => '+49 176 44333',
            'telefon1' => '+49 212 1399418',
            'telefon2' => '+49 176 555555',
            'telefon3' => '+49 176 5544466',
            'version' => 161,
            'vorname' => 'Philipp',
            'wiederverwendenFlag' => false,
            'zeitschriftenversand' => true,
            'mitgliedsNummer' => 53,
        ]);

        $member = $this->login()->member(1000, 1001);

        $this->assertSame(1, $member->feeId);
        $this->assertSame('2005-05-01 00:00:00', $member->joinedAt->toDateTimeString());
        $this->assertSame(1000, $member->groupId);
        $this->assertSame(1001, $member->id);
        $this->assertSame('pille@stamm-silva.de', $member->email);
        $this->assertSame('1991-06-20 00:00:00', $member->birthday->toDateTimeString());
        $this->assertSame(19, $member->genderId);
        $this->assertSame('aa@b.de', $member->emailParents);
        $this->assertSame(53, $member->memberId);
        $this->assertSame(1, $member->confessionId);
        $this->assertSame(1, $member->countryId);
        $this->assertSame('2022-03-20 11:58:33', $member->updatedAt->toDateTimeString());
        $this->assertSame('Lang', $member->lastname);
        $this->assertSame('zuss', $member->furtherAddress);
        $this->assertSame('Solingen', $member->location);
        $this->assertSame('42719', $member->zip);
        $this->assertSame(10, $member->regionId);
        $this->assertSame('pille', $member->nickname);
        $this->assertSame(1054, $member->nationalityId);
        $this->assertSame('lorem', $member->otherCountry);
        $this->assertSame('Maxstr 5', $member->address);
        $this->assertSame('+49 176 44333', $member->fax);
        $this->assertSame('+49 212 1399418', $member->mainPhone);
        $this->assertSame('+49 176 555555', $member->mobilePhone);
        $this->assertSame('+49 176 5544466', $member->workPhone);
        $this->assertSame(161, $member->version);
        $this->assertSame('Philipp', $member->firstname);
        $this->assertSame(false, $member->keepdata);
        $this->assertSame(true, $member->sendNewspaper);
    }

    public function testGetMinimalData(): void
    {
        app(MemberFake::class)->shows(1000, 1001, [
            'vorname' => '',
            'nachname' => '',
            'nameZusatz' => '',
            'spitzname' => '',
            'telefax' => '',
            'telefon1' => '',
            'telefon2' => '',
            'telefon3' => '',
            'staatsangehoerigkeitText' => '',
            'emailVertretungsberechtigter' => '',
            'mitgliedsNummer' => null,
            'beitragsartId' => null,
            'konfessionId' => null,
        ]);

        $member = $this->login()->member(1000, 1001);

        $this->assertNull($member->lastname);
        $this->assertNull($member->firstname);
        $this->assertNull($member->furtherAddress);
        $this->assertNull($member->nickname);
        $this->assertnull($member->fax);
        $this->assertnull($member->mainPhone);
        $this->assertnull($member->mobilePhone);
        $this->assertnull($member->workPhone);
        $this->assertnull($member->otherCountry);
        $this->assertNull($member->emailParents);
        $this->assertNull($member->memberId);
        $this->assertNull($member->feeId);
        $this->assertNull($member->confessionId);
    }

    public function testMemberFetchCanFail(): void
    {
        $this->expectException(NamiException::class);
        app(MemberFake::class)->fetchFails(103, 16);

        $this->login()->member(103, 16);
    }
}
