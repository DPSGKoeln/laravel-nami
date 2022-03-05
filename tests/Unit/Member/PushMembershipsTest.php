<?php

namespace Zoomyboy\LaravelNami\Tests\Unit\Member;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Zoomyboy\LaravelNami\Data\Membership;
use Zoomyboy\LaravelNami\Fakes\MembershipFake;
use Zoomyboy\LaravelNami\Member;
use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\Tests\TestCase;

class PushMembershipsTest extends TestCase
{

    public function test_create_a_membership(): void
    {
        Carbon::setTestNow(Carbon::parse('2021-02-03 06:00:00'));
        app(MembershipFake::class)->createsSuccessfully(16, 65);
        $this->login();

        $member = new Member(['id' => 16]);
        $id = $member->putMembership(Membership::fromArray([
            'startsAt' => now(),
            'groupId' => 150,
            'activityId' => 56,
            'subactivityId' => 89,
        ]));
        $this->assertEquals(65, $id);

        Http::assertSentCount(1);
        app(MembershipFake::class)->assertCreated(16, [
            'taetigkeitId' => 56,
            'untergliederungId' => 89,
            'aktivVon' => '2021-02-03T00:00:00',
            'gruppierungId' => 150,
        ]);
    }

}
