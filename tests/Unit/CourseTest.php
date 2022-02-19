<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Zoomyboy\LaravelNami\Authentication\Auth;
use Zoomyboy\LaravelNami\Exceptions\NotAuthenticatedException;
use Zoomyboy\LaravelNami\Fakes\CourseFake;
use Zoomyboy\LaravelNami\LoginException;
use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\NamiException;
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

    public function test_it_needs_login_to_get_courses(): void
    {
        app(CourseFake::class)->forMember(11111, [
            ['bausteinId' => 506, 'id' => 788, 'veranstalter' => 'KJA', 'vstgName' => 'eventname', 'vstgTag' => '2021-11-12 00:00:00']
        ]);
        $this->expectException(NotAuthenticatedException::class);

        Nami::coursesFor(11111);
    }

    public function test_store_a_course(): void
    {
        Auth::success(12345, 'secret');
        app(CourseFake::class)->createsSuccessful(123, 999);
        Nami::login(12345, 'secret');

        Nami::createCourse(123, [
            'event_name' => '::event::',
            'completed_at' => '2021-01-02 00:00:00',
            'organizer' => '::org::',
            'course_id' => 456
        ]);

        app(CourseFake::class)->assertCreated(123, [
            'bausteinId' => 456,
            'veranstalter' => '::org::',
            'vstgName' => '::event::',
            'vstgTag' => '2021-01-02T00:00:00',
        ]);
    }

    public function test_needs_login_to_store_a_course(): void
    {
        $this->expectException(NotAuthenticatedException::class);
        Nami::createCourse(123, [
            'event_name' => '::event::',
            'completed_at' => '2021-01-02 00:00:00',
            'organizer' => '::org::',
            'course_id' => 456
        ]);
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

    public function test_throw_exception_when_storing_failed(): void
    {
        $this->expectException(NamiException::class);
        Auth::success(12345, 'secret');
        app(CourseFake::class)->createFailed(123);
        Nami::login(12345, 'secret');

        Nami::createCourse(123, [
            'event_name' => '::event::',
            'completed_at' => '2021-01-02 00:00:00',
            'organizer' => '::org::',
            'course_id' => 456
        ]);
    }

}
