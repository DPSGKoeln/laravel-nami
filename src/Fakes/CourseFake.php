<?php

namespace Zoomyboy\LaravelNami\Fakes;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class CourseFake extends Fake {

    public function createsSuccessful(int $memberId, int $courseId): void
    {
        Http::fake(function($request) use ($memberId, $courseId) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}") {
                return Http::response([
                    'data' => $courseId,
                    'responseType' => 'OK',
                    'success' => true,
                ], 200);
            }
        });
    }

    public function doesntCreateWithError(int $memberId): void
    {
        Http::fake(function($request) use ($memberId) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}") {
                return Http::response('{"success":false,"data":null,"responseType":"EXCEPTION","message":"Unexpected Error javax.ejb.EJBException","title":null}', 200);
            }
        });
    }

    /**
     * @param int $memberId
     * @param array<string, mixed> $payload
     */
    public function assertCreated(int $memberId, array $payload): void
    {
        Http::assertSent(function($request) use ($memberId, $payload) {
            return $request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}"
                && $request->method() === 'POST'
                && data_get($request, 'bausteinId') === $payload['bausteinId']
                && data_get($request, 'veranstalter') === $payload['veranstalter']
                && data_get($request, 'vstgName') === $payload['vstgName']
                && data_get($request, 'vstgTag') === $payload['vstgTag'];
        });
    }

}
