<?php

namespace Zoomyboy\LaravelNami\Tests\Factories;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Http;
use Worksome\RequestFactories\RequestFactory;
use Zoomyboy\LaravelNami\Data\Member;

class MemberRequestFactory extends RequestFactory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'firstname' => $this->faker->firstName,
            'lastname' => $this->faker->lastName,
            'nickname' => $this->faker->firstName,
            'groupId' => $this->faker->numberBetween(100, 200),
            'genderId' => $this->faker->numberBetween(100, 200),
            'confessionId' => $this->faker->numberBetween(100, 200),
            'joinedAt' => $this->faker->dateTime()->format('Y-m-d').' 00:00:00',
            'birthday' => $this->faker->dateTime()->format('Y-m-d').' 00:00:00',
            'email' => $this->faker->safeEmail(),
            'countryId' => $this->faker->numberBetween(100, 200),
            'keepdata' => $this->faker->boolean(),
            'sendNewspaper' => $this->faker->boolean(),
            'regionId' => $this->faker->numberBetween(100, 200),
            'nationalityId' => $this->faker->numberBetween(100, 200),
            'beitragsartId' => $this->faker->numberBetween(100, 200),
            'id' => null,
        ];
    }

    public function inNami(int $groupId, int $namiId): self
    {
        return $this->state([
            'id' => $namiId,
            'groupId' => $groupId,
        ]);
    }

    public function withEmptyNames(): self
    {
        return $this->state([
            'vorname' => '',
            'nachname' => '',
            'nickname' => '',
        ]);
    }

    public function toSingleHttp(): PromiseInterface
    {
        return Http::response(json_encode([
            'success' => true,
            'message' => null,
            'title' => null,
            'data' => $this->create(),
            'responseType' => null,
        ]), 200);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function toMember(array $attributes = []): Member
    {
        return Member::from($this->create($attributes));
    }
}
