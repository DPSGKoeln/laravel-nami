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
                    'beitragsartId' => 1,
                    'eintrittsdatum' => '2005-05-01 00:00:00',
                    'email' => 'pille@stamm-silva.de',
                    'emailVertretungsberechtigter' => '',
                    'geburtsDatum' => '1991-06-20 00:00:00',
                    'geschlechtId' => 19,
                    'gruppierung' => 'gei0OhSh0quahcoh',
                    'gruppierungId' => $groupId,
                    'mitgliedsNummer' => 55,
                    'id' => $memberId,
                    'konfessionId' => 1,
                    'landId' => 1,
                    'lastUpdated' => '2022-03-20 11:58:33',
                    'nachname' => '::lastname::',
                    'nameZusatz' => 'zuss',
                    'ort' => 'SG',
                    'plz' => '12345',
                    'regionId' => 10,
                    'spitzname' => 'pille',
                    'staatsangehoerigkeitId' => 1054,
                    'staatsangehoerigkeitText' => '',
                    'strasse' => '::address::',
                    'telefax' => '+49 176 44333',
                    'telefon1' => '+49 212 1399418',
                    'telefon2' => '+49 176 555555',
                    'telefon3' => '+49 176 5544466',
                    'version' => 1,
                    'vorname' => '::firstname::',
                    'wiederverwendenFlag' => false,
                    'zeitschriftenversand' => true,
                ], $data));
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

    /**
     * @param array<string, string|int|null> $data
     */
    public function updatesSuccessfully(int $groupId, int $memberId, array $data = []): self
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
                if (!array_key_exists($key, $requestBody)) {
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
                if (!array_key_exists($key, $requestBody)) {
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
