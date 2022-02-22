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
            'totalEntries' => $collection->count(),
            'data' => $collection->toArray(),
        ]));
    }

    public function dataResponse(array $data): PromiseInterface
    {
        return Http::response(json_encode([
            'success' => true,
            'data' => $data,
        ]));
    }

    public function idResponse(int $id): PromiseInterface
    {
        return Http::response(json_encode([
            'success' => true,
            'data' => $id,
        ]));
    }

    public function htmlResponse(): PromiseInterface
    {
        return Http::response('<html></html>');
    }

}
