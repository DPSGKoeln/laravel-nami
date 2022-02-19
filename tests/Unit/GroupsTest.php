<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Zoomyboy\LaravelNami\Authentication\Auth;
use Zoomyboy\LaravelNami\Exceptions\NotAuthenticatedException;
use Zoomyboy\LaravelNami\Fakes\CourseFake;
use Zoomyboy\LaravelNami\Fakes\GroupFake;
use Zoomyboy\LaravelNami\LoginException;
use Zoomyboy\LaravelNami\Nami;
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
        app(GroupFake::class)->get([
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
        app(GroupFake::class)->get([
            1234 => ['name' => 'testgroup', 'children' => [
                555 => ['name' => 'ABC']
            ]]
        ]);

        $group = Nami::login(12345, 'secret')->group(1234)->subgroups()->first();

        $this->assertEquals('ABC', $group->name);
        $this->assertEquals(555, $group->id);

        app(GroupFake::class)->assertFetched(1234);
    }

}
