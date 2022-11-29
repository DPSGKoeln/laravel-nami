<?php

namespace Zoomyboy\LaravelNami\Tests\Factories;

use GuzzleHttp\Promise\FulfilledPromise;
use Illuminate\Support\Facades\Http;
use Worksome\RequestFactories\RequestFactory;

class MemberRequestFactory extends RequestFactory
{
    public function definition(): array
    {
        return [
            'vorname' => $this->faker->firstName,
            'nachname' => $this->faker->lastName,
            'nickname' => $this->faker->firstName,
        ];
    }

    public function withEmptyNames(): self
    {
        return $this->state([
            'vorname' => '',
            'nachname' => '',
            'nickname' => '',
        ]);
    }

    public function toSingleHttp(): FulfilledPromise
    {
        return Http::response(json_encode([
            'success' => true,
            'message' => null,
            'title' => null,
            'data' => $this->create(),
            'responseType' => null,
        ]), 200);
    }
}
