<?php

namespace Zoomyboy\LaravelNami\Tests\Factories;

use Worksome\RequestFactories\RequestFactory;
use Zoomyboy\LaravelNami\Data\MembershipEntry;

class MembershipEntryFactory extends RequestFactory
{
    public function definition(): array
    {
        return [
            'id' => $this->faker->numberBetween(100, 200),
            'group' => $this->faker->word(),
            'startsAt' => $this->faker->dateTime()->format('Y-m-d').' 00:00:00',
            'endsAt' => $this->faker->dateTime()->format('Y-m-d').' 00:00:00',
            'activity' => $this->faker->word(),
            'subactivity' => $this->faker->word(),
        ];
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function toMembership(array $attributes = []): MembershipEntry
    {
        return MembershipEntry::from($this->create($attributes));
    }

    public function id(int $id): self
    {
        return $this->state(['id' => $id]);
    }
}
