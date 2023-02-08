<?php

namespace Zoomyboy\LaravelNami\Tests\Unit\Api;

use Carbon\Carbon;
use Exception;
use Zoomyboy\LaravelNami\Data\Membership;
use Zoomyboy\LaravelNami\Exceptions\NoJsonReceivedException;
use Zoomyboy\LaravelNami\Exceptions\NotSuccessfulException;
use Zoomyboy\LaravelNami\Exceptions\RightException;
use Zoomyboy\LaravelNami\Fakes\MembershipFake;
use Zoomyboy\LaravelNami\Tests\TestCase;

class MembershipTest extends TestCase
{
    public function testMembershipIsInstanceOfDto(): void
    {
        app(MembershipFake::class)
            ->shows(6, [
                'id' => 10,
                'aktivBis' => '',
                'aktivVon' => '2013-05-06 00:00:00',
                'gruppierungId' => 1000,
                'taetigkeitId' => 15,
                'untergliederungId' => 16,
                'gruppierung' => '::group::',
            ]);

        $membership = $this->login()->membership(6, 10);

        $this->assertInstanceOf(Membership::class, $membership);
        $this->assertSame(10, $membership->id);
        $this->assertSame('2013-05-06 00:00:00', $membership->startsAt->toDateTimeString());
        $this->assertSame(null, $membership->endsAt);
        $this->assertSame(1000, $membership->groupId);
        $this->assertSame(15, $membership->activityId);
        $this->assertSame(16, $membership->subactivityId);
        $this->assertSame('::group::', $membership->group);
    }

    public function testFetchesMembership(): void
    {
        app(MembershipFake::class)->shows(6, ['id' => 10]);

        $this->login()->membership(6, 10);

        app(MembershipFake::class)->assertFetchedSingle(6, 10);
    }

    public function testThrowExceptionWhenFetchingFails(): void
    {
        app(MembershipFake::class)->failsShowing(6, 11);
        $this->expectException(NotSuccessfulException::class);

        $this->login()->membership(6, 11);
    }

    public function testReturnsNothingWhenFetchingFailsWithHtml(): void
    {
        $this->expectException(NoJsonReceivedException::class);
        app(MembershipFake::class)->failsShowingWithHtml(6, 11);

        $this->login()->membership(6, 11);
    }

    /**
     * @testWith ["Sicherheitsverletzung: Zugriff auf Rechte Recht (n:2001002 o:2) fehlgeschlagen", "Access denied - no right for requested operation"]
     */
    public function testItGetsNoMembershipsWithNoRights(string $error): void
    {
        $this->expectException(RightException::class);
        app(MembershipFake::class)->failsShowing(16, 68, $error);

        $membership = $this->login()->membership(16, 68);

        $this->assertNull($membership);
    }

    public function testItCanCreateAMembership(): void
    {
        Carbon::setTestNow(Carbon::parse('2022-02-03 03:00:00'));
        app(MembershipFake::class)->createsSuccessfully(6, 133);

        $membershipId = $this->login()->putMembership(6, Membership::from([
            'id' => null,
            'subactivityId' => 3,
            'activityId' => 2,
            'groupId' => 1400,
            'startsAt' => Carbon::parse('2022-02-03 00:00:00'),
            'endsAt' => null,
        ]));

        $this->assertEquals(133, $membershipId);
        app(MembershipFake::class)->assertCreated(6, [
            'taetigkeitId' => 2,
            'untergliederungId' => 3,
            'aktivVon' => '2022-02-03T00:00:00',
            'aktivBis' => '',
            'gruppierungId' => 1400,
        ]);
    }

    public function testItCanDeleteAMembership(): void
    {
        Carbon::setTestNow(Carbon::parse('2022-02-03 03:00:00'));
        app(MembershipFake::class)->destroysSuccessfully(6, 133);

        $this->login()->deleteMembership(6, Membership::from([
            'id' => 133,
            'subactivityId' => 3,
            'activityId' => 2,
            'groupId' => 1400,
            'startsAt' => Carbon::parse('2022-02-03 00:00:00'),
            'endsAt' => null,
        ]));

        app(MembershipFake::class)->assertDeleted(6, 133);
    }

    public function testItSetsAMembershipsEndDateWhenDeletingFails(): void
    {
        Carbon::setTestNow(Carbon::parse('2022-02-03 03:00:00'));
        app(MembershipFake::class)->failsDeleting(6, 133);
        app(MembershipFake::class)->updatesSuccessfully(6, 133);

        $this->login()->deleteMembership(6, Membership::from([
            'id' => 133,
            'subactivityId' => 3,
            'activityId' => 2,
            'groupId' => 1400,
            'startsAt' => Carbon::parse('2022-02-03 00:00:00'),
            'endsAt' => null,
        ]));

        app(MembershipFake::class)->assertUpdated(6, [
            'aktivBis' => '2022-02-03T00:00:00',
            'id' => 133,
        ]);
    }

    public function testItDoesntUpdateMembershipWhenNoIdGiven(): void
    {
        $this->expectException(Exception::class);
        Carbon::setTestNow(Carbon::parse('2022-02-03 03:00:00'));
        app(MembershipFake::class)->failsDeleting(6, null);
        app(MembershipFake::class)->updatesSuccessfully(6, null);

        $this->login()->deleteMembership(6, Membership::from([
            'id' => null,
            'subactivityId' => 3,
            'activityId' => 2,
            'groupId' => 1400,
            'startsAt' => Carbon::parse('2022-02-03 00:00:00'),
            'endsAt' => null,
        ]));
    }
}
