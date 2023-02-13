<?php

namespace Zoomyboy\LaravelNami\Tests\Factories;

use Carbon\Carbon;
use Worksome\RequestFactories\RequestFactory;
use Zoomyboy\LaravelNami\Data\Course;

class CourseFactory extends RequestFactory
{
    public function definition(): array
    {
        return [
            'courseId' => $this->faker->numberBetween(100, 200),
            'id' => $this->faker->numberBetween(100, 200),
            'organizer' => $this->faker->word(),
            'eventName' => $this->faker->word(),
            'completedAt' => Carbon::parse($this->faker->dateTime())->startOfDay(),
        ];
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function toCourse(array $attributes = []): Course
    {
        return Course::from($this->create($attributes));
    }

    public function id(int $id): self
    {
        return $this->state(['id' => $id]);
    }
}
