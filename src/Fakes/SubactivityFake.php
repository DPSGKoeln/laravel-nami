<?php

namespace Zoomyboy\LaravelNami\Fakes;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class SubactivityFake extends Fake {

    public function fetchFails(int $activityId, ?string $error = 'wrong message'): void
    {
        Http::fake(function($request) use ($activityId, $error) {
            if ($request->url() === 'https://nami.dpsg.de/ica/rest/nami/untergliederungauftaetigkeit/filtered/untergliederung/taetigkeit/'.$activityId) {
                return $this->errorResponse($error);
            }
        });
    }

}
