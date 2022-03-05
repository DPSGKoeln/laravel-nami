<?php

namespace Zoomyboy\LaravelNami\Fakes;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class MembershipFake extends Fake {

    public function fetches(int $memberId, array $membershipIds): self
    {
        Http::fake(function($request) use ($memberId, $membershipIds) {
            $url = 'https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/'.$memberId.'/flist';
            if ($request->url() === $url && $request->method() === 'GET') {
                return $this->collection(collect($membershipIds)->map(fn ($membershipId) => ['id' => $membershipId]));
            }
        });

        return $this;
    }

    public function failsFetching(int $memberId, string $error = 'Error'): self
    {
        Http::fake(function($request) use ($memberId, $error) {
            $url = 'https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/'.$memberId.'/flist';
            if ($request->url() === $url && $request->method() === 'GET') {
                return $this->errorResponse($error);
            }
        });

        return $this;
    }

    public function failsFetchingWithHtml(int $memberId): self
    {
        Http::fake(function($request) use ($memberId) {
            $url = 'https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/'.$memberId.'/flist';
            if ($request->url() === $url && $request->method() === 'GET') {
                return $this->htmlResponse();
            }
        });

        return $this;
    }

    public function shows(int $memberId, array $data): self
    {
        Http::fake(function($request) use ($memberId, $data) {
            $url = 'https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/'.$memberId.'/'.$data['id'];
            if ($request->url() === $url && $request->method() === 'GET') {
                return $this->dataResponse(array_merge([
                    "id" => 68,
                    "gruppierung" => "Diözesanleitung Köln 100000",
                    "gruppierungId" => 103,
                    "taetigkeit" => "ReferentIn",
                    "taetigkeitId" => 33,
                    "untergliederung" => "Pfadfinder",
                    "untergliederungId" => 55,
                    "aktivVon" => "2017-02-11 00:00:00",
                    "aktivBis" => "2017-03-11 00:00:00"
                ], $data));
            }
        });

        return $this;
    }

    public function failsShowing(int $memberId, int $membershipId, ?string $error = 'Error'): self
    {
        Http::fake(function($request) use ($memberId, $membershipId, $error) {
            $url = 'https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/'.$memberId.'/'.$membershipId;
            if ($request->url() === $url && $request->method() === 'GET') {
                return $this->errorResponse($error);
            }
        });

        return $this;
    }

    public function failsShowingWithHtml(int $memberId, int $membershipId): self
    {
        Http::fake(function($request) use ($memberId, $membershipId) {
            $url = 'https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/'.$memberId.'/'.$membershipId;
            if ($request->url() === $url && $request->method() === 'GET') {
                return $this->htmlResponse();
            }
        });

        return $this;
    }

    public function assertFetched(int $memberId): void
    {
        Http::assertSent(function($request) use ($memberId) {
            return $request->url() === "https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/{$memberId}/flist"
                && $request->method() === 'GET';
        });
    }

    public function assertFetchedSingle(int $memberId, int $membershipId): void
    {
        Http::assertSent(function($request) use ($memberId, $membershipId) {
            return $request->url() === "https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/{$memberId}/{$membershipId}"
                && $request->method() === 'GET';
        });
    }

}
