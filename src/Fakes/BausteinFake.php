<?php

namespace Zoomyboy\LaravelNami\Fakes;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class BausteinFake extends Fake {

    /**
     * @param int $memberId
     * @param array $courses
     *
     * @return self
     */
    public function fetches(array $courses): self
    {
        Http::fake(function($request) use ($courses) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/module/baustein") {
                return $this->collection(collect($courses));
            }
        });

        return $this;
    }

}
