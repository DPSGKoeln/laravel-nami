<?php

namespace Zoomyboy\LaravelNami\Fakes;

use Carbon\Carbon;
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

    public function deletes(int $memberId, Carbon $date): void
    {
        Http::fake(function ($request) use ($memberId, $date) {
            $url = 'https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/mglschaft-beenden';

            if ($request->url() !== $url || 'POST' !== $request->method()) {
                return null;
            }

            if ((int) data_get($request, 'id') !== $memberId) {
                return;
            }

            if ('true' !== data_get($request, 'isConfirmed')) {
                return;
            }

            if (Carbon::parse(data_get($request, 'beendenZumDatum'))->format('Y-m-d') !== $date->format('Y-m-d')) {
                return;
            }

            return $this->nullResponse();
        });
    }

    public function assertDeleted(int $memberId, Carbon $date): void
    {
        Http::assertSent(function ($request) use ($memberId, $date) {
            $url = 'https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/mglschaft-beenden';

            if ($request->url() !== $url || 'POST' !== $request->method()) {
                return false;
            }

            if ((int) data_get($request, 'id') !== $memberId) {
                return false;
            }

            if ('true' !== data_get($request, 'isConfirmed')) {
                return false;
            }

            if (Carbon::parse(data_get($request, 'beendenZumDatum'))->format('Y-m-d') !== $date->format('Y-m-d')) {
                return false;
            }

            return true;
        });
    }
}
