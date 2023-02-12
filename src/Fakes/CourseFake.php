<?php

namespace Zoomyboy\LaravelNami\Fakes;

use Illuminate\Support\Facades\Http;
use Zoomyboy\LaravelNami\Data\Course;

class CourseFake extends Fake
{
    /**
     * @param array<int> $ids
     */
    public function fetches(int $memberId, array $ids): self
    {
        Http::fake(function ($request) use ($memberId, $ids) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/flist") {
                return $this->collection(collect($ids)->map(fn ($id) => ['id' => $id]));
            }
        });

        return $this;
    }

    public function failsFetchingWithHtml(int $memberId): self
    {
        Http::fake(function ($request) use ($memberId) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/flist") {
                return $this->htmlResponse();
            }
        });

        return $this;
    }

    public function shows(int $memberId, Course $data): self
    {
        Http::fake(function ($request) use ($memberId, $data) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/{$data->id}") {
                return $this->dataResponse([
                    'bausteinId' => $data->courseId,
                    'veranstalter' => $data->organizer,
                    'vstgName' => $data->eventName,
                    'vstgTag' => $data->completedAt->format('Y-m-d').' 00:00:00',
                    'id' => $data->id,
                ]);
            }
        });

        return $this;
    }

    public function failsShowing(int $memberId, int $courseId, string $error = 'Error'): self
    {
        Http::fake(function ($request) use ($memberId, $courseId, $error) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/{$courseId}") {
                return $this->errorResponse($error);
            }
        });

        return $this;
    }

    public function failsShowingWithHtml(int $memberId, int $courseId): self
    {
        Http::fake(function ($request) use ($memberId, $courseId) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/{$courseId}") {
                return $this->htmlResponse();
            }
        });

        return $this;
    }

    public function createsSuccessfully(int $memberId, int $courseId): void
    {
        Http::fake(function ($request) use ($memberId, $courseId) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}" && 'POST' === $request->method()) {
                return $this->idResponse($courseId);
            }
        });
    }

    public function updatesSuccessfully(int $memberId, int $courseId): void
    {
        Http::fake(function ($request) use ($memberId, $courseId) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/{$courseId}" && 'PUT' === $request->method()) {
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
        Http::fake(function ($request) use ($memberId, $courseId) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/{$courseId}" && 'DELETE' === $request->method()) {
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
        Http::fake(function ($request) use ($memberId, $courseId) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/{$courseId}" && 'DELETE' === $request->method()) {
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
        Http::fake(function ($request) use ($memberId) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}") {
                return $this->errorResponse('Unexpected Error javaEx');
            }
        });
    }

    public function failsUpdating(int $memberId, int $courseId, string $error = 'Error'): void
    {
        Http::fake(function ($request) use ($memberId, $courseId, $error) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/{$courseId}" && 'PUT' === $request->method()) {
                return $this->errorResponse($error);
            }
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function assertCreated(int $memberId, array $payload): void
    {
        Http::assertSent(function ($request) use ($memberId, $payload) {
            return $request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}"
                && 'POST' === $request->method()
                && data_get($request, 'bausteinId') === $payload['bausteinId']
                && data_get($request, 'veranstalter') === $payload['veranstalter']
                && data_get($request, 'vstgName') === $payload['vstgName']
                && data_get($request, 'vstgTag') === $payload['vstgTag'];
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function assertUpdated(int $memberId, int $courseId, array $payload): void
    {
        Http::assertSent(function ($request) use ($memberId, $courseId, $payload) {
            return $request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/{$courseId}"
                && 'PUT' === $request->method()
                && data_get($request, 'bausteinId') === $payload['bausteinId']
                && data_get($request, 'veranstalter') === $payload['veranstalter']
                && data_get($request, 'vstgName') === $payload['vstgName']
                && data_get($request, 'vstgTag') === $payload['vstgTag'];
        });
    }

    public function assertDeleted(int $memberId, int $courseId): void
    {
        Http::assertSent(function ($request) use ($memberId, $courseId) {
            return $request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/{$courseId}"
                && 'DELETE' === $request->method();
        });
    }

    public function assertFetched(int $memberId): void
    {
        Http::assertSent(function ($request) use ($memberId) {
            return $request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/flist"
                && 'GET' === $request->method();
        });
    }

    public function assertFetchedSingle(int $memberId, int $courseId): void
    {
        Http::assertSent(function ($request) use ($memberId, $courseId) {
            return $request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/{$courseId}"
                && 'GET' === $request->method();
        });
    }
}
