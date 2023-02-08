<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Zoomyboy\LaravelNami\Authentication\Auth;
use Zoomyboy\LaravelNami\Exceptions\NotSuccessfulException;
use Zoomyboy\LaravelNami\Fakes\BausteinFake;
use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\Tests\TestCase;

class BausteinTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Auth::fake();
    }

    public function testGetAllCourses(): void
    {
        Auth::success(12345, 'secret');
        app(BausteinFake::class)->fetches([['id' => 788, 'descriptor' => 'abc']]);

        $courses = Nami::login(12345, 'secret')->courses();

        $this->assertCount(1, $courses);

        $this->assertEquals(788, $courses->first()->id);
        $this->assertEquals('abc', $courses->first()->name);
    }

    public function testThrowExceptionWhenBausteinFetchingFails(): void
    {
        $this->expectException(NotSuccessfulException::class);
        Auth::success(12345, 'secret');
        app(BausteinFake::class)->failsToFetch();

        Nami::login(12345, 'secret')->courses();
    }
}
