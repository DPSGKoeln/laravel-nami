<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Zoomyboy\LaravelNami\Authentication\Auth;
use Zoomyboy\LaravelNami\Exceptions\NotAuthenticatedException;
use Zoomyboy\LaravelNami\Fakes\CourseFake;
use Zoomyboy\LaravelNami\LoginException;
use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\Tests\TestCase;

class CourseTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();
    
        Auth::fake();
    }

    public function test_get_courses_of_member(): void
    {
        Auth::success(12345, 'secret');
        app(CourseFake::class)->forMember(11111, [
            ['bausteinId' => 506, 'id' => 788, 'veranstalter' => 'KJA', 'vstgName' => 'eventname', 'vstgTag' => '2021-11-12 00:00:00']
        ]);

        $courses = Nami::login(12345, 'secret')->coursesFor(11111);

        $this->assertEquals(788, $courses->first()->id);
        $this->assertEquals('KJA', $courses->first()->organizer);
        $this->assertEquals(506, $courses->first()->course_id);
        $this->assertEquals('eventname', $courses->first()->event_name);
        $this->assertEquals('2021-11-12 00:00:00', $courses->first()->completed_at);

        Http::assertSent(function($request) {
            return $request->url() == 'https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/11111/flist';
        });
        Http::assertSent(function($request) {
            return $request->url() == 'https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/11111/788';
        });
        Http::assertSentCount(2);
    }

    public function test_needs_login(): void
    {
        $this->expectException(NotAuthenticatedException::class);

        $courses = Nami::coursesFor(11111);
    }

    public function test_parses_failed_login(): void
    {
        Auth::failed(12345, 'secret');
        $this->expectException(LoginException::class);

        Nami::login(12345, 'secret')->coursesFor(11111);
    }

}
