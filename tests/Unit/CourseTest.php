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
        app(CourseFake::class)
            ->fetches(11111, [788])
            ->fetchesSingle(11111, [
                'bausteinId' => 506,
                'id' => 788,
                'veranstalter' => 'KJA',
                'vstgName' => 'eventname',
                'vstgTag' => '2021-11-12 00:00:00'
            ]);

        $course = Nami::login(12345, 'secret')->coursesFor(11111)->first();

        $this->assertEquals(788, $course->id);
        $this->assertEquals('KJA', $course->organizer);
        $this->assertEquals(506, $course->course_id);
        $this->assertEquals('eventname', $course->event_name);
        $this->assertEquals('2021-11-12 00:00:00', $course->completed_at);

        app(CourseFake::class)->assertFetched(11111);
        app(CourseFake::class)->assertFetchedSingle(11111, 788);
    }

    public function test_it_gets_multiple_courses_of_member(): void
    {
        Auth::success(12345, 'secret');
        app(CourseFake::class)
            ->fetches(11111, [788, 789])
            ->fetchesSingle(11111, ['id' => 788])
            ->fetchesSingle(11111, ['id' => 789]);

        $courses = Nami::login(12345, 'secret')->coursesFor(11111);

        $this->assertCount(2, $courses);
    }

    public function test_return_nothing_when_course_returns_html(): void
    {
        Auth::success(12345, 'secret');
        app(CourseFake::class)
            ->fetches(11111, [788, 789])
            ->failsFetchingSingleWithHtml(11111, 788)
            ->fetchesSingle(11111, ['id' => 789]);

        $courses = Nami::login(12345, 'secret')->coursesFor(11111);

        $this->assertCount(1, $courses);
    }

    public function test_return_empty_when_course_index_returns_html(): void
    {
        Auth::success(12345, 'secret');
        app(CourseFake::class)->failsFetchingWithHtml(11111);

        $courses = Nami::login(12345, 'secret')->coursesFor(11111);

        $this->assertCount(0, $courses);
    }

    public function test_it_needs_login_to_get_courses(): void
    {
        $this->expectException(NotAuthenticatedException::class);

        Nami::coursesFor(11111);
    }

    public function test_store_a_course(): void
    {
        Auth::success(12345, 'secret');
        app(CourseFake::class)->createsSuccessfully(123, 999);
        Nami::login(12345, 'secret')->createCourse(123, [
            'event_name' => '::event::',
            'completed_at' => '2021-01-02 00:00:00',
            'organizer' => '::org::',
            'course_id' => 456
        ]);

        app(CourseFake::class)->assertCreated(123, [
            'vstgName' => '::event::',
            'vstgTag' => '2021-01-02T00:00:00',
            'veranstalter' => '::org::',
            'bausteinId' => 456,
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

    public function test_update_a_course(): void
    {
        Auth::success(12345, 'secret');
        app(CourseFake::class)->updatesSuccessfully(123, 999);

        Nami::login(12345, 'secret')->updateCourse(123, 999, [
            'event_name' => '::event::',
            'completed_at' => '2021-01-02 00:00:00',
            'organizer' => '::org::',
            'course_id' => 456,
            'id' => 999,
        ]);

        app(CourseFake::class)->assertUpdated(123, 999, [
            'vstgName' => '::event::',
            'vstgTag' => '2021-01-02T00:00:00',
            'veranstalter' => '::org::',
            'bausteinId' => 456,
            'id' => 999,
        ]);
    }

    public function test_throw_exception_when_course_update_failed(): void
    {
        $this->expectException(NamiException::class);
        Auth::success(12345, 'secret');
        app(CourseFake::class)->failsUpdating(123, 999);

        Nami::login(12345, 'secret')->updateCourse(123, 999, [
            'event_name' => '::event::',
            'completed_at' => '2021-01-02 00:00:00',
            'organizer' => '::org::',
            'course_id' => 456,
            'id' => 999,
        ]);
    }

    public function test_it_needs_valid_credentials_to_store_a_course(): void
    {
        Auth::failed(12345, 'secret');
        $this->expectException(NotAuthenticatedException::class);
        Nami::createCourse(123, [
            'event_name' => '::event::',
            'completed_at' => '2021-01-02 00:00:00',
            'organizer' => '::org::',
            'course_id' => 456
        ]);
    }

    public function test_it_throws_login_exception_when_fetching_with_wrong_credentials(): void
    {
        Auth::failed(12345, 'secret');
        $this->expectException(LoginException::class);

        Nami::login(12345, 'secret')->coursesFor(11111);
    }

    public function test_throw_exception_when_storing_failed(): void
    {
        $this->expectException(NamiException::class);
        Auth::success(12345, 'secret');
        app(CourseFake::class)->failsCreating(123);
        Nami::login(12345, 'secret');

        Nami::createCourse(123, [
            'event_name' => '::event::',
            'completed_at' => '2021-01-02 00:00:00',
            'organizer' => '::org::',
            'course_id' => 456
        ]);
    }

    public function test_delete_a_course(): void
    {
        Auth::success(12345, 'secret');
        app(CourseFake::class)->deletesSuccessfully(123, 999);

        Nami::login(12345, 'secret')->deleteCourse(123, 999);

        app(CourseFake::class)->assertDeleted(123, 999);
    }

    public function test_shrow_exception_when_deleting_failed(): void
    {
        $this->expectException(NamiException::class);
        Auth::success(12345, 'secret');
        app(CourseFake::class)->failsDeleting(123, 999);

        Nami::login(12345, 'secret')->deleteCourse(123, 999);
    }

}
