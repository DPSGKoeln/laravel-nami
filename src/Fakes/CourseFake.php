<?php

namespace Zoomyboy\LaravelNami\Fakes;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class CourseFake extends Fake {

    private array $defaults = [
        'bausteinId' => 506,
        'veranstalter' => 'KJA',
        'vstgName' => 'eventname',
        'vstgTag' => '2021-11-12 00:00:00'
    ];

    /**
     * @param int $memberId
     * @param array<int> $ids
     *
     * @return self
     */
    public function fetches(int $memberId, array $ids): self
    {
        Http::fake(function($request) use ($memberId, $ids) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/flist") {
                return $this->collection(collect($ids)->map(fn ($id) => ['id' => $id]));
            }
        });

        return $this;
    }

    public function failsFetchingWithHtml(int $memberId): self
    {
        Http::fake(function($request) use ($memberId) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/flist") {
                return $this->htmlResponse();
            }
        });

        return $this;
    }

    /**
     * @param int $memberId
     * @param array<string, mixed> $data
     *
     * @return self
     */
    public function fetchesSingle(int $memberId, array $data): self
    {
        Http::fake(function($request) use ($memberId, $data) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/{$data['id']}") {
                return $this->dataResponse(array_merge($this->defaults, $data));
            }
        });

        return $this;
    }

    public function failsFetchingSingle(int $memberId, int $courseId, string $error = 'Error'): self
    {
        Http::fake(function($request) use ($memberId, $courseId, $error) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/{$courseId}") {
                return $this->errorResponse($error);
            }
        });

        return $this;
    }

    public function failsFetchingSingleWithHtml(int $memberId, int $courseId): self
    {
        Http::fake(function($request) use ($memberId, $courseId) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/{$courseId}") {
                return $this->htmlResponse();
            }
        });

        return $this;
    }

    public function createsSuccessfully(int $memberId, int $courseId): void
    {
        Http::fake(function($request) use ($memberId, $courseId) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}" && $request->method() === 'POST') {
                return $this->idResponse($courseId);
            }
        });
    }

    public function updatesSuccessfully(int $memberId, int $courseId): void
    {
        Http::fake(function($request) use ($memberId, $courseId) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/{$courseId}" && $request->method() === 'PUT') {
                return Http::response([
                    'data' => $courseId,
                    'responseType' => 'OK',
                    'success' => true,
                ], 200);
            }
        });
    }

    public function deletesSuccessfully(int $memberId, int $courseId): void
    {
        Http::fake(function($request) use ($memberId, $courseId) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/{$courseId}" && $request->method() === 'DELETE') {
                return Http::response([
                    'data' => null,
                    'responseType' => 'OK',
                    'success' => true,
                ], 200);
            }
        });
    }

    public function failsDeleting(int $memberId, int $courseId): void
    {
        Http::fake(function($request) use ($memberId, $courseId) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/{$courseId}" && $request->method() === 'DELETE') {
                return Http::response([
                    'data' => null,
                    'responseType' => 'NOK',
                    'success' => false,
                ], 200);
            }
        });
    }

    public function failsCreating(int $memberId): void
    {
        Http::fake(function($request) use ($memberId) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}") {
                return $this->errorResponse("Unexpected Error javaEx");
            }
        });
    }

    public function failsUpdating(int $memberId, int $courseId, string $error = "Error"): void
    {
        Http::fake(function($request) use ($memberId, $courseId, $error) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/{$courseId}" && $request->method() === 'PUT') {
                return $this->errorResponse($error);
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

    /**
     * @param int $memberId
     * @param int $courseId
     * @param array<string, mixed> $payload
     */
    public function assertUpdated(int $memberId, int $courseId, array $payload): void
    {
        Http::assertSent(function($request) use ($memberId, $courseId, $payload) {
            return $request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/${courseId}"
                && $request->method() === 'PUT'
                && data_get($request, 'bausteinId') === $payload['bausteinId']
                && data_get($request, 'veranstalter') === $payload['veranstalter']
                && data_get($request, 'vstgName') === $payload['vstgName']
                && data_get($request, 'vstgTag') === $payload['vstgTag'];
        });
    }

    public function assertDeleted(int $memberId, int $courseId): void
    {
        Http::assertSent(function($request) use ($memberId, $courseId) {
            return $request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/${courseId}"
                && $request->method() === 'DELETE';
        });
    }

    public function assertFetched(int $memberId): void
    {
        Http::assertSent(function($request) use ($memberId) {
            return $request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/flist"
                && $request->method() === 'GET';
        });
    }

    public function assertFetchedSingle(int $memberId, int $courseId): void
    {
        Http::assertSent(function($request) use ($memberId, $courseId) {
            return $request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/{$courseId}"
                && $request->method() === 'GET';
        });
    }

}
