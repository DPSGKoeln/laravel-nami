<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Zoomyboy\LaravelNami\Fakes\MembershipFake;
use Zoomyboy\LaravelNami\Member;
use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\Tests\TestCase;

class MemberTest extends TestCase
{

    public function test_get_memberships_of_a_member(): void
    {
        app(MembershipFake::class)
            ->fetches(16, [68, 69])
            ->shows(16, [
                "id" => 68,
            ]);
        $this->login();
        $member = new Member(['id' => 16]);

        $membership = $member->memberships()->first();

        $this->assertEquals(68, $membership->id);
    }

    /**
     * @testWith ["Access denied - no right for requested operation", "Sicherheitsverletzung: Zugriff auf Rechte Recht (n:2001002 o:2) fehlgeschlagen"]
     */
    public function test_it_gets_no_memberships_with_no_rights(string $error): void
    {
        app(MembershipFake::class)->fetchFails(16, $error);
        $this->login();
        $member = new Member(['id' => 16]);

        $memberships = $member->memberships();

        $this->assertSame([], $member->memberships()->toArray());
    }
}
