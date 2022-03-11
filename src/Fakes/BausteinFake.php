<?php

namespace Zoomyboy\LaravelNami\Fakes;

use Illuminate\Support\Facades\Http;

class BausteinFake extends Fake
{
    /**
     * @param int $memberId
     */
    public function fetches(array $courses): self
    {
        Http::fake(function ($request) use ($courses) {
            if ('https://nami.dpsg.de/ica/rest/module/baustein' === $request->url()) {
                return $this->collection(collect($courses));
            }
        });

        return $this;
    }

    public function failsToFetch(): self
    {
        Http::fake(function ($request) {
            if ('https://nami.dpsg.de/ica/rest/module/baustein' === $request->url()) {
                return $this->errorResponse('error');
            }
        });

        return $this;
    }
}
