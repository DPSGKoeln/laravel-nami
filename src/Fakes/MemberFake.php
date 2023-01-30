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

    public function shows(int $groupId, int $memberId, array $data = []): self
    {
        Http::fake(function ($request) use ($groupId, $memberId, $data) {
            $url = 'https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/'.$groupId.'/'.$memberId;
            if ($request->url() === $url && 'GET' === $request->method()) {
                return $this->dataResponse(array_merge([
                    'id' => $memberId,
                    'vorname' => '::firstname::',
                    'nachname' => '::lastname::',
                    'zeitschriftenversand' => true,
                    'strasse' => '::address::',
                    'plz' => '12345',
                    'ort' => 'SG',
                    'version' => 1,
                    'eintrittsdatum' => '2005-05-01 00:00:00',
                    'geburtsDatum' => '1991-06-20 00:00:00',
                    'gruppierungId' => $groupId,
                    'gruppierung' => 'gei0OhSh0quahcoh',
                    'geschlechtId' => 19,
                    'staatsangehoerigkeitId' => 1054,
                ], $data));
            }
        });

        return $this;
    }

    /**
     * @param array<string, string|int|null> $data
     */
    public function updates(int $groupId, int $memberId, array $data): self
    {
        Http::fake(function ($request) use ($groupId, $memberId, $data) {
            $url = 'https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/'.$groupId.'/'.$memberId;
            if ($request->url() === $url && 'PUT' === $request->method()) {
                return $this->dataResponse([
                    'id' => $memberId,
                    ...$data,
                ]);
            }
        });

        return $this;
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

    public function createsSuccessfully(int $groupId, int $memberId): self
    {
        Http::fake(function ($request) use ($memberId, $groupId) {
            $url = "https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/{$groupId}";

            if ($request->url() === $url || 'POST' === $request->method()) {
                return $this->idResponse($memberId);
            }
        });

        return $this;
    }

    /**
     * @param array<string, string|int|null> $body
     */
    public function assertCreated(int $groupId, array $body): void
    {
        Http::assertSent(function ($request) use ($groupId, $body) {
            $url = "https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/{$groupId}";

            if ($request->url() !== $url || 'POST' !== $request->method()) {
                return false;
            }

            $requestBody = json_decode($request->body(), true);

            foreach ($body as $key => $value) {
                if (!isset($requestBody[$key])) {
                    return false;
                }

                if ($requestBody[$key] !== $value) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * @param array<string, string|int|null> $body
     */
    public function assertUpdated(int $groupId, int $memberId, array $body): void
    {
        Http::assertSent(function ($request) use ($groupId, $memberId, $body) {
            $url = "https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/{$groupId}/{$memberId}";

            if ($request->url() !== $url || 'PUT' !== $request->method()) {
                return false;
            }

            $requestBody = json_decode($request->body(), true);

            foreach ($body as $key => $value) {
                if (!isset($requestBody[$key])) {
                    return false;
                }

                if ($requestBody[$key] !== $value) {
                    return false;
                }
            }

            return true;
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
