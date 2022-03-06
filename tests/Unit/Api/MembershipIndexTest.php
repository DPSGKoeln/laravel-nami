<?php

namespace Zoomyboy\LaravelNami\Tests\Unit\Api;

use Carbon\Carbon;
use Zoomyboy\LaravelNami\Data\Membership;
use Zoomyboy\LaravelNami\Data\MembershipEntry;
use Zoomyboy\LaravelNami\Exceptions\RightException;
use Zoomyboy\LaravelNami\Fakes\MembershipFake;
use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\NamiException;
use Zoomyboy\LaravelNami\Tests\TestCase;

class MembershipIndexTest extends TestCase
{

    public function testGetMembershipsCount(): void
    {
        app(MembershipFake::class)->fetches(6, [10, 11]);

        $memberships = $this->login()->membershipsOf(6);

        $this->assertCount(2, $memberships);
    }

    public function testMembershipIsInstanceOfDto(): void
    {
        app(MembershipFake::class)
            ->fetches(6, [[
                'entries_aktivBis' => '2021-02-04 00:00:00',
                'entries_aktivVon' => '2021-02-03 00:00:00',
                'entries_untergliederung' => '::unter::',
                'entries_taetigkeit' => 'Leiter (6)',
                'id' => 55,
                'entries_gruppierung' => '::group::',
            ]]);

        $membership = $this->login()->membershipsOf(6)->first();

        $this->assertInstanceOf(MembershipEntry::class, $membership);
        $this->assertSame(55, $membership->id);
        $this->assertSame('2021-02-03 00:00:00', $membership->startsAt->toDateTimeString());
        $this->assertSame('2021-02-04 00:00:00', $membership->endsAt->toDateTimeString());
        $this->assertSame('::group::', $membership->group);
        $this->assertSame('Leiter (6)', $membership->activity);
        $this->assertSame('::unter::', $membership->subactivity);
    }


    public function testStringsCanBeNull(): void
    {
        app(MembershipFake::class)
            ->fetches(6, [[
                'entries_aktivBis' => '',
                'entries_untergliederung' => '',
            ]]);

        $membership = $this->login()->membershipsOf(6)->first();

        $this->assertNull($membership->endsAt);
        $this->assertNull($membership->subactivity);
    }


}
