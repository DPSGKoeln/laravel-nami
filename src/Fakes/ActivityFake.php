<?php

namespace Zoomyboy\LaravelNami\Fakes;

use Illuminate\Support\Facades\Http;

class ActivityFake extends Fake
{
    /**
     * @param array<int, array{descriptor: string, id: int}> $data
     */
    public function fetches(int $groupId, array $data): self
    {
        Http::fake(function ($request) use ($data, $groupId) {
            $url = "https://nami.dpsg.de/ica/rest/nami/taetigkeitaufgruppierung/filtered/gruppierung/gruppierung/{$groupId}";
            if ($request->url() === $url && 'GET' === $request->method()) {
                return $this->dataResponse($data);
            }
        });

        return $this;
    }

    /**
     * @param array<int, array{descriptor: string, id: int}> $data
     */
    public function fetchesSubactivity(int $activityId, array $data): self
    {
        Http::fake(function ($request) use ($data, $activityId) {
            $url = "https://nami.dpsg.de/ica/rest/nami/untergliederungauftaetigkeit/filtered/untergliederung/taetigkeit/{$activityId}";
            if ($request->url() === $url && 'GET' === $request->method()) {
                return $this->dataResponse($data);
            }
        });

        return $this;
    }
}
