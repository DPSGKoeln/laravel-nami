<?php

namespace Zoomyboy\LaravelNami\Fakes;

use Illuminate\Support\Facades\Http;

class SearchFake extends Fake
{
    public function fetchFails(int $page, int $start, ?string $error = 'wrong message'): void
    {
        Http::fake(function ($request) use ($error, $page, $start) {
            if ($request->url() === 'https://nami.dpsg.de/ica/rest/nami/search-multi/result-list?searchedValues='.rawurlencode('{}').'&page='.$page.'&start='.$start.'&limit=100') {
                return $this->errorResponse($error);
            }
        });
    }
}
