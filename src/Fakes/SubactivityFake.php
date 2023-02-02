<?php

namespace Zoomyboy\LaravelNami\Fakes;

use Illuminate\Support\Facades\Http;

class SubactivityFake extends Fake
{
    /**
     * @param array<int, array{descriptor: string, id: int}> $data
     */
    public function fetches(int $activityId, array $data): self
    {
        Http::fake(function ($request) use ($data, $activityId) {
            $url = "https://nami.dpsg.de/ica/rest/nami/untergliederungauftaetigkeit/filtered/untergliederung/taetigkeit/$activityId";
            if ($request->url() === $url && 'GET' === $request->method()) {
                return $this->dataResponse($data);
            }
        });

        return $this;
    }

    public function fetchFails(int $activityId, ?string $error = 'wrong message'): void
    {
        Http::fake(function ($request) use ($activityId, $error) {
            if ($request->url() === 'https://nami.dpsg.de/ica/rest/nami/untergliederungauftaetigkeit/filtered/untergliederung/taetigkeit/'.$activityId) {
                return $this->errorResponse($error);
            }
        });
    }

    public function fetchFailsWithoutJson(int $activityId): void
    {
        Http::fake(function ($request) use ($activityId) {
            if ($request->url() === 'https://nami.dpsg.de/ica/rest/nami/untergliederungauftaetigkeit/filtered/untergliederung/taetigkeit/'.$activityId) {
                return $this->htmlResponse();
            }
        });
    }
}
