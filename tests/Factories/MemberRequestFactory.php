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

    public function toSingleHttp(): FulfilledPromise
    {
        return Http::response($this->create(), 200);
    }
}
