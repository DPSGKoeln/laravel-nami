<?php

namespace Zoomyboy\LaravelNami\Fakes;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Http;

abstract class Fake {

    public function errorResponse(string $error): PromiseInterface
    {
        return Http::response(json_encode([
            'success' => false,
            'message' => $error,
        ]));
    }

}
