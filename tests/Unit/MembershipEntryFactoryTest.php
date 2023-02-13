<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Carbon\Carbon;
use ReflectionClass;
use Zoomyboy\LaravelNami\Data\MembershipEntry;
use Zoomyboy\LaravelNami\Tests\TestCase;

class MembershipEntryFactoryTest extends TestCase
{
    public function testItCanRenderACourseAsJson(): void
    {
        $membership = MembershipEntry::factory()->toMembership();

        $json = $membership->toArray();
        $data = json_decode(json_encode($json));

        $newMembership = MembershipEntry::from($data);

        foreach ((new ReflectionClass(MembershipEntry::class))->getMethod('__construct')->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (!is_object($membership->{$name})) {
                $this->assertSame($newMembership->{$name}, $membership->{$name});
            }

            if (is_a($membership->{$name}, Carbon::class)) {
                $this->assertSame($membership->{$name}->toDateTimeString(), $newMembership->{$name}->toDateTimeString());
            }
        }
    }
}
