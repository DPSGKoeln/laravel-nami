<?php

namespace Zoomyboy\LaravelNami\Fakes;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

abstract class Fake {

    public function errorResponse(string $error): PromiseInterface
    {
        return Http::response(json_encode([
            'success' => false,
            'message' => $error,
        ]));
    }

    public function collection(Collection $collection): PromiseInterface
    {
        return Http::response(json_encode([
            'success' => true,
            'data' => $collection->toArray(),
        ]));
    }

    public function data(array $data): PromiseInterface
    {
        return Http::response(json_encode([
            'success' => true,
            'data' => $data,
        ]));
    }

    public function htmlResponse(): PromiseInterface
    {
        return Http::response('<html></html>');
    }

}
