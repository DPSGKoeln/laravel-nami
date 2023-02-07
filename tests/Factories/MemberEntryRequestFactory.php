<?php

namespace Zoomyboy\LaravelNami\Tests\Factories;

use Worksome\RequestFactories\RequestFactory;
use Zoomyboy\LaravelNami\Data\MemberEntry;

class MemberEntryRequestFactory extends RequestFactory
{
    public function definition(): array
    {
        return [
            'firstname' => $this->faker->firstName(),
            'lastname' => $this->faker->lastName(),
            'groupId' => $this->faker->numberBetween(100, 200),
            'id' => $this->faker->numberBetween(100, 200),
        ];
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function toMember(array $attributes = []): MemberEntry
    {
        return MemberEntry::from($this->create($attributes));
    }
}
