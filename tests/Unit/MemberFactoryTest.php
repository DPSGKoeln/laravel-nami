<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Carbon\Carbon;
use ReflectionClass;
use Zoomyboy\LaravelNami\Data\Member;
use Zoomyboy\LaravelNami\Tests\TestCase;

class MemberFactoryTest extends TestCase
{
    public function testItCanRenderMemberAsJson(): void
    {
        $member = Member::factory()->toMember();

        $json = $member->toArray();
        $data = json_decode(json_encode($json));

        $newMember = Member::from($data);

        foreach ((new ReflectionClass(Member::class))->getMethod('__construct')->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (!is_object($member->{$name})) {
                $this->assertSame($newMember->{$name}, $member->{$name});
            }

            if (is_a($member->{$name}, Carbon::class)) {
                $this->assertSame($member->{$name}->toDateTimeString(), $newMember->{$name}->toDateTimeString());
            }
        }
    }
}
