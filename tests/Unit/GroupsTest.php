<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Zoomyboy\LaravelNami\Authentication\Auth;
use Zoomyboy\LaravelNami\Exceptions\NotAuthenticatedException;
use Zoomyboy\LaravelNami\Fakes\CourseFake;
use Zoomyboy\LaravelNami\Fakes\GroupFake;
use Zoomyboy\LaravelNami\LoginException;
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

    public function test_get_groups(): void
    {
        Auth::success(12345, 'secret');
        app(GroupFake::class)->fetches(null, [
            1234 => ['name' => 'testgroup']
        ]);

        $group = Nami::login(12345, 'secret')->group(1234);

        $this->assertEquals('testgroup', $group->name);
        $this->assertEquals(1234, $group->id);

        app(GroupFake::class)->assertRootFetched();
    }

    public function test_get_subgroups(): void
    {
        Auth::success(12345, 'secret');
        app(GroupFake::class)->fetches(null, [
            1234 => ['name' => 'testgroup']
        ])->fetches(1234, [
            555 => ['name' => 'ABC']
        ]);

        $group = Nami::login(12345, 'secret')->groups(1234)->first();

        $this->assertEquals('ABC', $group->name);
        $this->assertEquals(555, $group->id);

        app(GroupFake::class)->assertFetched(1234);
    }

    public function test_needs_authentication(): void
    {
        $this->expectException(NotAuthenticatedException::class);
        $group = Nami::group(1234);
    }

    public function test_throws_exception_when_group_fetch_failed(): void
    {
        $this->expectException(NamiException::class);
        Auth::success(12345, 'secret');
        app(GroupFake::class)->failsToFetch(null);

        Nami::login(12345, 'secret')->group(1234);
    }

    public function test_throws_exception_when_subgroup_fetch_failed(): void
    {
        $this->expectException(NamiException::class);
        Auth::success(12345, 'secret');
        app(GroupFake::class)->fetches(null, [
            1234 => ['name' => 'testgroup']
        ]);
        app(GroupFake::class)->failsToFetch(1234);

        Nami::login(12345, 'secret')->groups(1234);
    }

    public function test_it_doesnt_return_group_when_no_json_is_returned(): void
    {
        Auth::success(12345, 'secret');
        app(GroupFake::class)->failsToFetchWithoutJson(null);

        $group = Nami::login(12345, 'secret')->group(1234);
        $this->assertNull($group);
    }

}
