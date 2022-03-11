<?php

namespace Zoomyboy\LaravelNami\Tests\Unit\Member;

use Zoomyboy\LaravelNami\Fakes\MembershipFake;
use Zoomyboy\LaravelNami\Member;
use Zoomyboy\LaravelNami\Tests\TestCase;

class MembershipTest extends TestCase
{
    public function testGetMembershipsOfAMember(): void
    {
        app(MembershipFake::class)
            ->fetches(16, [68])
            ->shows(16, [
                'id' => 68,
            ]);
        $this->login();
        $member = new Member(['id' => 16]);

        $membership = $member->memberships()->first();

        $this->assertEquals(68, $membership->id);
    }

    /**
     * @testWith ["Access denied - no right for requested operation", "Sicherheitsverletzung: Zugriff auf Rechte Recht (n:2001002 o:2) fehlgeschlagen"]
     */
    public function testItGetsNoMembershipsWithNoRights(string $error): void
    {
        app(MembershipFake::class)->failsFetching(16, $error);
        $this->login();
        $member = new Member(['id' => 16]);

        $memberships = $member->memberships();

        $this->assertSame([], $memberships->toArray());
    }
}
