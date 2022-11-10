<?php

namespace Zoomyboy\LaravelNami\Fakes;

use Illuminate\Support\Facades\Http;

class MembershipFake extends Fake
{
    /**
     * @param array<int, int|array{id: int, entries_taetigkeit?: string, entries_aktivVon?: string, entries_aktivBis?: string, entries_untergliederung?: string, entries_gruppierung?: string}> $membershipIds
     */
    public function fetches(int $memberId, array $membershipIds): self
    {
        Http::fake(function ($request) use ($memberId, $membershipIds) {
            $url = 'https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/'.$memberId.'/flist';
            if ($request->url() === $url && 'GET' === $request->method()) {
                return $this->collection(collect($membershipIds)->map(function ($membership) {
                    return [
                        ...[
                            'entries_aktivBis' => '2021-02-04 00:00:00',
                            'entries_aktivVon' => '2021-02-03 00:00:00',
                            'entries_untergliederung' => '::unter::',
                            'entries_taetigkeit' => 'Leiter (6)',
                            'id' => 55,
                            'entries_gruppierung' => '::group::',
                        ],
                        ...(is_array($membership) ? $membership : ['id' => $membership]),
                    ];
                }));
            }
        });

        return $this;
    }

    public function failsFetching(int $memberId, string $error = 'Error'): self
    {
        Http::fake(function ($request) use ($memberId, $error) {
            $url = 'https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/'.$memberId.'/flist';
            if ($request->url() === $url && 'GET' === $request->method()) {
                return $this->errorResponse($error);
            }
        });

        return $this;
    }

    public function failsFetchingWithHtml(int $memberId): self
    {
        Http::fake(function ($request) use ($memberId) {
            $url = 'https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/'.$memberId.'/flist';
            if ($request->url() === $url && 'GET' === $request->method()) {
                return $this->htmlResponse();
            }
        });

        return $this;
    }

    /**
     * @param array{id: int, gruppierung?: string, taetigkeit?: string, taetigkeitId?: int, untergliederung?: string, untergliederungId?: int, aktivVon?: string, aktivBis?: string} $data
     */
    public function shows(int $memberId, array $data): self
    {
        Http::fake(function ($request) use ($memberId, $data) {
            $url = 'https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/'.$memberId.'/'.$data['id'];
            if ($request->url() === $url && 'GET' === $request->method()) {
                return $this->dataResponse(array_merge([
                    'id' => 68,
                    'gruppierung' => 'Diözesanleitung Köln 100000',
                    'gruppierungId' => 103,
                    'taetigkeit' => 'ReferentIn',
                    'taetigkeitId' => 33,
                    'untergliederung' => 'Pfadfinder',
                    'untergliederungId' => 55,
                    'aktivVon' => '2017-02-11 00:00:00',
                    'aktivBis' => '2017-03-11 00:00:00',
                ], $data));
            }
        });

        return $this;
    }

    public function failsShowing(int $memberId, int $membershipId, ?string $error = 'Error'): self
    {
        Http::fake(function ($request) use ($memberId, $membershipId, $error) {
            $url = 'https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/'.$memberId.'/'.$membershipId;
            if ($request->url() === $url && 'GET' === $request->method()) {
                return $this->errorResponse($error);
            }
        });

        return $this;
    }

    public function failsCreating(int $memberId, ?string $error = 'Error'): self
    {
        Http::fake(function ($request) use ($memberId, $error) {
            $url = "https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/{$memberId}";
            if ($request->url() === $url && 'POST' === $request->method()) {
                return $this->errorResponse($error);
            }
        });

        return $this;
    }

    public function failsShowingWithHtml(int $memberId, int $membershipId): self
    {
        Http::fake(function ($request) use ($memberId, $membershipId) {
            $url = 'https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/'.$memberId.'/'.$membershipId;
            if ($request->url() === $url && 'GET' === $request->method()) {
                return $this->htmlResponse();
            }
        });

        return $this;
    }

    public function assertFetched(int $memberId): void
    {
        Http::assertSent(function ($request) use ($memberId) {
            return $request->url() === "https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/{$memberId}/flist"
                && 'GET' === $request->method();
        });
    }

    public function assertFetchedSingle(int $memberId, int $membershipId): void
    {
        Http::assertSent(function ($request) use ($memberId, $membershipId) {
            return $request->url() === "https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/{$memberId}/{$membershipId}"
                && 'GET' === $request->method();
        });
    }

    public function createsSuccessfully(int $memberId, int $membershipId): void
    {
        Http::fake(function ($request) use ($memberId, $membershipId) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/{$memberId}" && 'POST' === $request->method()) {
                return $this->idResponse($membershipId);
            }
        });
    }

    public function updatesSuccessfully(int $memberId, ?int $membershipId): void
    {
        Http::fake(function ($request) use ($memberId, $membershipId) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/{$memberId}/{$membershipId}" && 'PUT' === $request->method()) {
                return $this->dataResponse(['id' => $membershipId]);
            }
        });
    }

    public function deletesSuccessfully(int $memberId, int $membershipId): void
    {
        Http::fake(function ($request) use ($memberId, $membershipId) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/{$memberId}/{$membershipId}" && 'DELETE' === $request->method()) {
                return $this->nullResponse();
            }
        });
    }

    public function failsDeleting(int $memberId, ?int $membershipId): void
    {
        Http::fake(function ($request) use ($memberId, $membershipId) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/{$memberId}/{$membershipId}" && 'DELETE' === $request->method()) {
                return $this->errorResponse('');
            }
        });
    }

    public function assertDeleted(int $memberId, int $membershipId): void
    {
        $url = "https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/{$memberId}/{$membershipId}";
        Http::assertSent(function ($request) use ($url) {
            return $request->url() === $url && 'DELETE' === $request->method();
        });
    }

    public function assertCreated(int $memberId, array $payload): void
    {
        $url = "https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/{$memberId}";
        Http::assertSent(function ($request) use ($url, $payload) {
            if ($request->url() !== $url || 'POST' !== $request->method()) {
                return false;
            }

            if (
                data_get($request, 'gruppierungId') !== data_get($payload, 'gruppierungId')
                || data_get($request, 'id') !== data_get($payload, 'id')
                || data_get($request, 'taetigkeitId') !== data_get($payload, 'taetigkeitId')
                || data_get($request, 'untergliederungId') !== data_get($payload, 'untergliederungId')
            ) {
                return false;
            }

            if (data_get($request, 'aktivVon') && $request['aktivVon'] !== data_get($payload, 'aktivVon')) {
                return false;
            }

            return true;
        });
    }

    public function assertUpdated(int $memberId, array $payload): void
    {
        $url = "https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/{$memberId}/{$payload['id']}";
        Http::assertSent(function ($request) use ($url, $payload) {
            if ($request->url() !== $url || 'PUT' !== $request->method()) {
                return false;
            }

            if (data_get($request, 'id') !== $payload['id']) {
                return false;
            }

            if (data_get($request, 'aktivBis') !== data_get($payload, 'aktivBis')) {
                return false;
            }

            return true;
        });
    }
}
