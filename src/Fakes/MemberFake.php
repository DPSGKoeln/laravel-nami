<?php

namespace Zoomyboy\LaravelNami\Fakes;

use Illuminate\Support\Facades\Http;

class MemberFake extends Fake
{
    public function fetchFails(int $groupId, int $memberId, string $error = 'wrong message'): void
    {
        Http::fake(function ($request) use ($groupId, $memberId, $error) {
            $url = 'https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/'.$groupId.'/'.$memberId;
            if ($request->url() === $url && 'GET' === $request->method()) {
                return $this->errorResponse($error);
            }
        });
    }

    public function shows(int $groupId, int $memberId, array $data): void
    {
        Http::fake(function ($request) use ($groupId, $memberId, $data) {
            $url = 'https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/'.$groupId.'/'.$memberId;
            if ($request->url() === $url && 'GET' === $request->method()) {
                return $this->dataResponse(array_merge([
                ], $data));
            }
        });
    }
}
