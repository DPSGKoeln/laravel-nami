<?php

namespace Zoomyboy\LaravelNami\Support;

use Generator;
use Illuminate\Http\Client\Response;
use Illuminate\Support\LazyCollection;

class Paginator
{
    /**
     * @param callable(int): Response       $responseFactory
     * @param callable(Response): Generator $generator
     * @param callable(Response): int       $totalFetcher
     */
    public function result(callable $responseFactory, callable $generator, callable $totalFetcher): LazyCollection
    {
        return $this->startResult(
            0,
            fn ($page, $start) => $responseFactory($page),
            $generator,
            $totalFetcher
        );
    }

    /**
     * @param callable(int, int): Response  $responseFactory
     * @param callable(Response): Generator $generator
     * @param callable(Response): int       $totalFetcher
     */
    public function startResult(int $perPage, callable $responseFactory, callable $generator, callable $totalFetcher): LazyCollection
    {
        return LazyCollection::make(function () use ($perPage, $responseFactory, $generator, $totalFetcher) {
            $page = 1;
            $start = 0;
            $fetchedEntries = 0;
            while (!isset($totalEntries) || $fetchedEntries < $totalEntries) {
                $response = $responseFactory($page, $start);

                foreach ($generator($response) as $entry) {
                    ++$fetchedEntries;
                    yield $entry;
                }

                $totalEntries = $totalFetcher($response);
                ++$page;
                $start += $perPage;
            }
        });
    }
}
