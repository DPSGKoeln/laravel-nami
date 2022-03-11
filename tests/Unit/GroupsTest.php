<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Zoomyboy\LaravelNami\Authentication\Auth;
use Zoomyboy\LaravelNami\Exceptions\NotAuthenticatedException;
use Zoomyboy\LaravelNami\Fakes\GroupFake;
use Zoomyboy\LaravelNami\Group;
use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\NamiException;
use Zoomyboy\LaravelNami\Tests\TestCase;

class GroupsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Auth::fake();
    }

    public function testGetGroups(): void
    {
        Auth::success(12345, 'secret');
        app(GroupFake::class)->fetches(null, [
            1234 => ['name' => 'testgroup'],
        ]);

        $group = Nami::login(12345, 'secret')->group(1234);

        $this->assertInstanceOf(Group::class, $group);
        $this->assertEquals('testgroup', $group->name);
        $this->assertEquals(1234, $group->id);

        app(GroupFake::class)->assertRootFetched();
    }

    public function testGetSubgroups(): void
    {
        Auth::success(12345, 'secret');
        app(GroupFake::class)->fetches(null, [
            1234 => ['name' => 'testgroup'],
        ])->fetches(1234, [
            555 => ['name' => 'ABC'],
        ]);

        $group = Nami::login(12345, 'secret')->groups(1234)->first();

        $this->assertEquals('ABC', $group->name);
        $this->assertEquals(555, $group->id);

        app(GroupFake::class)->assertFetched(1234);
    }

    public function testNeedsAuthentication(): void
    {
        $this->expectException(NotAuthenticatedException::class);
        $group = Nami::group(1234);
    }

    public function testThrowsExceptionWhenGroupFetchFailed(): void
    {
        $this->expectException(NamiException::class);
        Auth::success(12345, 'secret');
        app(GroupFake::class)->failsToFetch(null);

        Nami::login(12345, 'secret')->group(1234);
    }

    public function testThrowsExceptionWhenSubgroupFetchFailed(): void
    {
        $this->expectException(NamiException::class);
        Auth::success(12345, 'secret');
        app(GroupFake::class)->fetches(null, [
            1234 => ['name' => 'testgroup'],
        ]);
        app(GroupFake::class)->failsToFetch(1234);

        Nami::login(12345, 'secret')->groups(1234);
    }

    public function testItDoesntReturnGroupWhenNoJsonIsReturned(): void
    {
        Auth::success(12345, 'secret');
        app(GroupFake::class)->failsToFetchWithoutJson(null);

        $group = Nami::login(12345, 'secret')->group(1234);
        $this->assertNull($group);
    }
}
