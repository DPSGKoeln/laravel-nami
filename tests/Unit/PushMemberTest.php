<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Zoomyboy\LaravelNami\Data\Member;
use Zoomyboy\LaravelNami\Fakes\MemberFake;
use Zoomyboy\LaravelNami\Tests\TestCase;

class PushMemberTest extends TestCase
{
    public function testPushASingleMember(): void
    {
        app(MemberFake::class)->createsSuccessfully(103, 16);
        $response = $this->login()->putMember(Member::from([
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
        ]));

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
            'wiederverwendenFlag' => false,
            'zeitschriftenversand' => false,
            'regionId' => 11,
            'staatsangehoerigkeitId' => 12,
        ]);
    }
}
