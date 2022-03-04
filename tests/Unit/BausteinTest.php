<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Zoomyboy\LaravelNami\Authentication\Auth;
use Zoomyboy\LaravelNami\Exceptions\NotAuthenticatedException;
use Zoomyboy\LaravelNami\Fakes\BausteinFake;
use Zoomyboy\LaravelNami\Fakes\CourseFake;
use Zoomyboy\LaravelNami\LoginException;
use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\NamiException;
use Zoomyboy\LaravelNami\Tests\TestCase;

class BausteinTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();
    
        Auth::fake();
    }

    public function test_get_all_courses(): void
    {
        Auth::success(12345, 'secret');
        app(BausteinFake::class)->fetches([['id' => 788, 'descriptor' => 'abc']]);

        $courses = Nami::login(12345, 'secret')->courses();

        $this->assertCount(1, $courses);

        $this->assertEquals(788, $courses->first()->id);
        $this->assertEquals('abc', $courses->first()->name);
    }

}
