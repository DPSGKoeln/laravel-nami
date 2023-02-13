<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Carbon\Carbon;
use ReflectionClass;
use Zoomyboy\LaravelNami\Data\Course;
use Zoomyboy\LaravelNami\Tests\TestCase;

class CourseFactoryTest extends TestCase
{
    public function testItCanRenderACourseAsJson(): void
    {
        $course = Course::factory()->toCourse();

        $json = $course->toArray();
        $data = json_decode(json_encode($json));

        $newCourse = Course::from($data);

        foreach ((new ReflectionClass(Course::class))->getMethod('__construct')->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (!is_object($course->{$name})) {
                $this->assertSame($newCourse->{$name}, $course->{$name});
            }

            if (is_a($course->{$name}, Carbon::class)) {
                $this->assertSame($course->{$name}->toDateTimeString(), $newCourse->{$name}->toDateTimeString());
            }
        }
    }
}
