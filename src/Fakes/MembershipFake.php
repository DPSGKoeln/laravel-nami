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

    public function fetchFails(int $memberId, string $error): self
    {
        Http::fake(function($request) use ($memberId, $error) {
            $url = 'https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/'.$memberId.'/flist';
            if ($request->url() === $url && $request->method() === 'GET') {
                return $this->errorResponse($error);
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

    public function failsToShow(int $memberId, int $membershipId, string $error): self
    {
        Http::fake(function($request) use ($memberId, $membershipId, $error) {
            $url = 'https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/'.$memberId.'/'.$membershipId;
            if ($request->url() === $url && $request->method() === 'GET') {
                return $this->errorResponse($error);
            }
        });

        return $this;
    }

}
