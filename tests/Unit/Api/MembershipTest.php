<?php

namespace Zoomyboy\LaravelNami\Tests\Unit\Api;

use Carbon\Carbon;
use Zoomyboy\LaravelNami\Data\Membership;
use Zoomyboy\LaravelNami\Exceptions\RightException;
use Zoomyboy\LaravelNami\Fakes\MembershipFake;
use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\NamiException;
use Zoomyboy\LaravelNami\Tests\TestCase;

class MembershipTest extends TestCase
{

    public function testGetMembershipsCount(): void
    {
        app(MembershipFake::class)
            ->fetches(6, [10, 11])
            ->shows(6, ['id' => 10])
            ->shows(6, ['id' => 11]);

        $memberships = $this->login()->membershipsOf(6);

        $this->assertCount(2, $memberships);
    }

    public function testMembershipIsInstanceOfDto(): void
    {
        app(MembershipFake::class)
            ->fetches(6, [10])
            ->shows(6, [
                'id' => 10,
                'aktivBis' => '',
                'aktivVon' => '2013-05-06 00:00:00',
                'gruppierungId' => 1000,
                'taetigkeitId' => 15,
                'untergliederungId' => 16,
            ]);

        $membership = $this->login()->membershipsOf(6)->first();

        $this->assertInstanceOf(Membership::class, $membership);
        $this->assertSame(10, $membership->id);
        $this->assertSame('2013-05-06 00:00:00', $membership->startsAt->toDateTimeString());
        $this->assertSame(null, $membership->endsAt);
        $this->assertSame(1000, $membership->groupId);
        $this->assertSame(15, $membership->activityId);
        $this->assertSame(16, $membership->subactivityId);
    }

    public function testFetchesMembership(): void
    {
        app(MembershipFake::class)
            ->fetches(6, [10])
            ->shows(6, ['id' => 10]);

        $this->login()->membershipsOf(6);

        app(MembershipFake::class)->assertFetched(6);
        app(MembershipFake::class)->assertFetchedSingle(6, 10);
    }

    public function testThrowExceptionWhenFetchingFails(): void
    {
        app(MembershipFake::class)->failsFetching(6);
        $this->expectException(NamiException::class);

        $this->login()->membershipsOf(6);
    }

    public function testReturnsNothingWhenFetchingFailsWithHtml(): void
    {
        app(MembershipFake::class)->failsFetchingWithHtml(6);

        $memberships = $this->login()->membershipsOf(6);

        $this->assertCount(0, $memberships);
    }

    public function testThrowExceptionWhenFetchingSingleFails(): void
    {
        app(MembershipFake::class)
            ->fetches(6, [55])
            ->failsShowing(6, 55);
        $this->expectException(NamiException::class);

        $this->login()->membershipsOf(6);
    }

    public function testDontReturnSingleThatReturnsHtml(): void
    {
        app(MembershipFake::class)
            ->fetches(6, [55, 66])
            ->shows(6, ['id' => 55])
            ->failsShowingWithHtml(6, 66);

        $memberships = $this->login()->membershipsOf(6);

        $this->assertCount(1, $memberships);
    }

    public function testItFetchesSingleMembership(): void
    {
        app(MembershipFake::class)->shows(16, [ "id" => 68 ]);

        $membership = $this->login()->membership(16, 68);

        $this->assertSame(68, $membership->id);
    }

    /**
     * @testWith ["Sicherheitsverletzung: Zugriff auf Rechte Recht (n:2001002 o:2) fehlgeschlagen", "Access denied - no right for requested operation"]
     */
    public function test_it_gets_no_memberships_with_no_rights(string $error): void
    {
        app(MembershipFake::class)->failsShowing(16, 68, $error);

        $membership = $this->login()->membership(16, 68);

        $this->assertNull($membership);
    }

    public function testItCanCreateAMembership(): void
    {
        Carbon::setTestNow(Carbon::parse('2022-02-03 03:00:00'));
        app(MembershipFake::class)->createsSuccessfully(6, 133);

        $membershipId = $this->login()->putMembership(6, Membership::fromArray([
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

}
