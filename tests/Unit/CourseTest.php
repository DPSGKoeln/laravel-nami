<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Zoomyboy\LaravelNami\Data\Course;
use Zoomyboy\LaravelNami\Exceptions\NoJsonReceivedException;
use Zoomyboy\LaravelNami\Exceptions\NotAuthenticatedException;
use Zoomyboy\LaravelNami\Exceptions\NotSuccessfulException;
use Zoomyboy\LaravelNami\Fakes\CourseFake;
use Zoomyboy\LaravelNami\LoginException;
use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\Tests\TestCase;

class CourseTest extends TestCase
{
    public function testGetCoursesOfMember(): void
    {
        app(CourseFake::class)
            ->fetches(11111, [788])
            ->shows(11111, Course::factory()->toCourse([
                'bausteinId' => 506,
                'id' => 788,
                'veranstalter' => 'KJA',
                'vstgName' => 'eventname',
                'vstgTag' => '2021-11-12 00:00:00',
            ]));

        $course = $this->login()->coursesOf(11111)->first();

        $this->assertEquals(788, $course->id);
        $this->assertEquals('KJA', $course->organizer);
        $this->assertEquals(506, $course->courseId);
        $this->assertEquals('eventname', $course->eventName);
        $this->assertEquals('2021-11-12 00:00:00', $course->completedAt);

        app(CourseFake::class)->assertFetched(11111);
        app(CourseFake::class)->assertFetchedSingle(11111, 788);
    }

    public function testItGetsMultipleCoursesOfMember(): void
    {
        app(CourseFake::class)
            ->fetches(11111, [788, 789])
            ->shows(11111, Course::factory()->id(788)->toCourse())
            ->shows(11111, Course::factory()->id(789)->toCourse());

        $courses = $this->login()->coursesOf(11111);

        $this->assertCount(2, $courses);
    }

    public function testReturnNothingWhenCourseReturnsHtml(): void
    {
        app(CourseFake::class)
            ->fetches(11111, [788, 789])
            ->failsShowingWithHtml(11111, 788)
            ->shows(11111, Course::factory()->id(789)->toCourse());

        $courses = $this->login()->coursesOf(11111);

        $this->assertCount(1, $courses);
    }

    public function testReturnEmptyWhenCourseIndexReturnsHtml(): void
    {
        $this->expectException(NoJsonReceivedException::class);
        app(CourseFake::class)->failsFetchingWithHtml(11111);

        $this->login()->coursesOf(11111);
    }

    public function testItNeedsLoginToGetCourses(): void
    {
        $this->expectException(NotAuthenticatedException::class);

        Nami::coursesOf(11111);
    }

    public function testStoreACourse(): void
    {
        app(CourseFake::class)->createsSuccessfully(123, 999);
        $this->login()->createCourse(123, [
            'event_name' => '::event::',
            'completed_at' => '2021-01-02 00:00:00',
            'organizer' => '::org::',
            'course_id' => 456,
        ]);

        app(CourseFake::class)->assertCreated(123, [
            'vstgName' => '::event::',
            'vstgTag' => '2021-01-02T00:00:00',
            'veranstalter' => '::org::',
            'bausteinId' => 456,
        ]);
    }

    public function testNeedsLoginToStoreACourse(): void
    {
        $this->expectException(NotAuthenticatedException::class);
        Nami::createCourse(123, [
            'event_name' => '::event::',
            'completed_at' => '2021-01-02 00:00:00',
            'organizer' => '::org::',
            'course_id' => 456,
        ]);
    }

    public function testUpdateACourse(): void
    {
        app(CourseFake::class)->updatesSuccessfully(123, 999);

        $this->login()->updateCourse(123, 999, [
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

    public function testThrowExceptionWhenCourseUpdateFailed(): void
    {
        $this->expectException(NotSuccessfulException::class);
        app(CourseFake::class)->failsUpdating(123, 999);

        $this->login()->updateCourse(123, 999, [
            'event_name' => '::event::',
            'completed_at' => '2021-01-02 00:00:00',
            'organizer' => '::org::',
            'course_id' => 456,
            'id' => 999,
        ]);
    }

    public function testItNeedsValidCredentialsToStoreACourse(): void
    {
        $this->expectException(NotAuthenticatedException::class);
        Nami::createCourse(123, [
            'event_name' => '::event::',
            'completed_at' => '2021-01-02 00:00:00',
            'organizer' => '::org::',
            'course_id' => 456,
        ]);
    }

    public function testItThrowsLoginExceptionWhenFetchingWithWrongCredentials(): void
    {
        $this->expectException(LoginException::class);

        $this->loginWithWrongCredentials()->coursesOf(11111);
    }

    public function testThrowExceptionWhenStoringFailed(): void
    {
        $this->expectException(NotSuccessfulException::class);
        app(CourseFake::class)->failsCreating(123);

        $this->login()->createCourse(123, [
            'event_name' => '::event::',
            'completed_at' => '2021-01-02 00:00:00',
            'organizer' => '::org::',
            'course_id' => 456,
        ]);
    }

    public function testDeleteACourse(): void
    {
        app(CourseFake::class)->deletesSuccessfully(123, 999);

        $this->login()->deleteCourse(123, 999);

        app(CourseFake::class)->assertDeleted(123, 999);
    }

    public function testShrowExceptionWhenDeletingFailed(): void
    {
        $this->expectException(NotSuccessfulException::class);
        app(CourseFake::class)->failsDeleting(123, 999);

        $this->login()->deleteCourse(123, 999);
    }
}
