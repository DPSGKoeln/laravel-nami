<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Zoomyboy\LaravelNami\Fakes\MembershipFake;
use Zoomyboy\LaravelNami\Tests\TestCase;

class PullMembershipTest extends TestCase
{
    public function testGetASingleMembership(): void
    {
        app(MembershipFake::class)
            ->fetches(105, [['id' => 56, 'entries_taetigkeit' => 'Leiter']]);

        $memberships = $this->login()->membershipsOf(105);

        $this->assertEquals('Leiter', $memberships->first()->activity);
    }
}
