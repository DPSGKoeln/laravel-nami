<?php

namespace Zoomyboy\LaravelNami\Backend;

use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Zoomyboy\LaravelNami\Fakes\Fake;
use Zoomyboy\LaravelNami\Fakes\FakeInstance;
use Zoomyboy\LaravelNami\Fakes\LoginFake;

class FakeBackend {

    public ?array $loggedIn = null;

    public function __construct() {
        $this->members = collect([]);
    }

    public function addMember(array $member) {
        $member['mitgliedsNummer'] = $member['id'];
        $this->members->push($member);
    }

    public function init($cookie) {
        return Http::withOptions(['cookies' => $cookie->forBackend()]);
    }

    public function put($url, $data) {
        if (is_null($this->cookie->getCookieByName('JSESSIONID'))) {
            return $this->notAuthorizedResponse();
        }

        if (preg_match('|/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/([0-9]+)/([0-9]+)|', $url, $matches)) {
            list($url, $groupId, $memberId) = $matches;
            $existing = $this->members->search(function($m) use ($groupId, $memberId) {
                return $m['gruppierungId'] == $groupId && $m['id'] == $memberId;
            });
            if ($existing !== false) {
                $this->members[$existing] = $data;
            }

            return $this->response([
                'id' => $memberId
            ]);
        }

        $this->urlNotFoundException($url);
    }

    public function get($url) {
        if (preg_match('|/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/([0-9]+)/flist|', $url, $matches)) {
            list($url, $groupId) = $matches;

            $members = $this->members->filter(function($m) use ($groupId) {
                return $m['gruppierungId'] == $groupId;
            })->map(function($member) {
                return [
                    "entries_id" => $member['id'],
                    "id" => $member['id'],
                    "entries_mitgliedsNummer" => $member['id'],
                ];
            });
            return $this->response([
                "success" => true,
                'data' => $members,
                "responseType" => "OK",
                "metaData" => []
            ]);
        }

        if (preg_match('|/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/([0-9]+)/([0-9]+)|', $url, $matches)) {
            list($url, $groupId, $memberId) = $matches;

            $member = $this->members->first(function($m) use ($groupId, $memberId) {
                return $m['gruppierungId'] == $groupId && $m['id'] == $memberId;
            });

            return new Response(new GuzzleResponse(200, [], json_encode([
                'success' => true,
                'data' => $member
            ])));
        }

        if ($url === 'https://nami.dpsg.de/ica/pages/login.jsp') {
            return;
        }

        if ($url === 'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root') {
            $groups = collect(data_get($this->groups, $this->loggedInAs))->map(function($group) {
                return [
                    "descriptor" => "Solingen-Wald, Silva 100105",
                    "name" => "",
                    "representedClass" => "de.iconcept.nami.entity.org.Gruppierung",
                    "id" => $group
                ];
            })->toArray();

            return $this->response([
                "success" => true,
                "data" => $groups,
                "responseType" => "OK"
            ]);
        }

        if (Str::contains($url, 'search-multi/result-list')) {
            $query = parse_url($url)['query'];
            parse_str($query, $q);
            $params = json_decode($q['searchedValues'], true);
            if (array_keys($params) === ['mitgliedsNummber']) {
                return $this->findNr($params['mitgliedsNummber']);
            }
        }

        $this->urlNotFoundException($url);
    }

    public function findNr($nr) {
        $found = $this->members->first(fn($m) => $m['id'] === $nr);

        $found = [
            'entries_mitgliedsNummer' => $found['mitgliedsNummer'],
            'entries_vorname' => $found['vorname'],
            'entries_nachname' => $found['nachname'],
        ];

        return $this->response([
            "success" => true,
            "data" => [$found],
            "responseType" => "OK",
            "totalEntries" => 1
        ]);
    }

    private function wrongLoginResponse() {
        return $this->response([
            "servicePrefix" => null,
            "methodCall" => null,
            "response" => null,
            "statusCode" => 3000,
            "statusMessage" => "Benutzer nicht gefunden oder Passwort falsch.",
            "apiSessionName" => "JSESSIONID",
            "apiSessionToken" => "qrjlt_YFVhtRPU-epc-58AB1",
            "minorNumber" => 0,
            "majorNumber" => 0,
        ]);
    }

    public function response($data) {
        return new Response(new GuzzleResponse(200, [], json_encode($data)));
    }

    /**
     * @param string $mglnr
     */
    public function fakeLogin(string $mglnr): self
    {
        app(LoginFake::class)->succeeds($mglnr);

        return $this;
    }

    /**
     * @param int $mitgliedsNr
     * @param array <string, mixed> $data
     */
    public function addSearch(int $mitgliedsNr, array $data): self
    {
        Http::fake(function($request) use ($data, $mitgliedsNr) {
            if ($request->url() === 'https://nami.dpsg.de/ica/rest/nami/search-multi/result-list?searchedValues='.rawurlencode(json_encode(['mitgliedsNummber' => $mitgliedsNr]) ?: '{}').'&page=1&start=0&limit=10') {
                $content = [
                    'success' => true,
                    'data' => [$data],
                    'responseType' => 'OK',
                    'totalEntries' => 1,
                ];
                return Http::response(json_encode($content) ?: '{}', 200);
            }
        });

        return $this;
    }

    /**
     * @param array<int, array{name: string, id: int}> $data
     */
    public function fakeNationalities(array $data): self
    {
        Http::fake(function($request) use ($data) {
            if ($request->url() === 'https://nami.dpsg.de/ica/rest/baseadmin/staatsangehoerigkeit') {
                return $this->dataResponse($data);
            }
        });

        return $this;
    }

    /**
     * @param array<string, string> $data
     */
    public function fakeMember(array $data): self
    {
        Http::fake(function($request) use ($data) {
            if ($request->url() === 'https://nami.dpsg.de/ica/rest/nami/search-multi/result-list?searchedValues='.rawurlencode('[]').'&page=1&start=0&limit=10') {
                return Http::response(json_encode([
                    'success' => true,
                    'data' => [[
                        'entries_id' => $data['id'],
                        'entries_gruppierungId' => $data['gruppierungId'],
                    ]]
                ]) ?: '{}', 200);
            }

            if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/{$data['gruppierungId']}/{$data['id']}") {
                $content = [
                    'success' => true,
                    'data' => $data,
                ];

                return Http::response(json_encode($content) ?: '{}', 200);
            }
        });

        return $this;
    }

    /**
     * @param array<int, array{name: string, id: int}> $data
     */
    public function fakeCountries(array $data): self
    {
        Http::fake(function($request) use ($data) {
            if ($request->url() === 'https://nami.dpsg.de/ica/rest/baseadmin/land') {
                return $this->dataResponse($data);
            }
        });

        return $this;
    }

    /**
     * @param array<int, array{name: string, id: int}> $data
     */
    public function fakeGenders(array $data): self
    {
        Http::fake(function($request) use ($data) {
            if ($request->url() === 'https://nami.dpsg.de/ica/rest/baseadmin/geschlecht') {
                return $this->dataResponse($data);
            }
        });

        return $this;
    }

    /**
     * @param array<int, array{name: string, id: int}> $data
     */
    public function fakeRegions(array $data): self
    {
        Http::fake(function($request) use ($data) {
            if ($request->url() === 'https://nami.dpsg.de/ica/rest/baseadmin/region') {
                return $this->dataResponse($data);
            }
        });

        return $this;
    }

    /**
     * @param int $groupId
     * @param array<int, array{name: string, id: int}> $data
     */
    public function fakeActivities(int $groupId, array $data): self
    {
        Http::fake(function($request) use ($data, $groupId) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/taetigkeitaufgruppierung/filtered/gruppierung/gruppierung/{$groupId}") {
                return $this->dataResponse($data);
            }
        });

        return $this;
    }

    /**
     * @param int $groupId
     * @param array<int, array<int, array{name: string, id: int}>> $data
     */
    public function fakeSubactivities($matches): self
    {
        Http::fake(function($request) use ($matches) {
            foreach ($matches as $activityId => $data) {
                if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/untergliederungauftaetigkeit/filtered/untergliederung/taetigkeit/{$activityId}") {
                    return $this->dataResponse($data);
                }
            }
        });

        return $this;
    }

    /**
     * @param int $groupId
     * @param array<int, array{name: string, id: int}> $data
     */
    public function fakeFees(int $groupId, array $data): self
    {
        Http::fake(function($request) use ($data, $groupId) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/namiBeitrag/beitragsartmgl/gruppierung/{$groupId}") {
                return $this->dataResponse($data);
            }
        });

        return $this;
    }

    /**
     * @param array<int, array{name: string, id: int}> $data
     */
    public function fakeConfessions(array $data): self
    {
        Http::fake(function($request) use ($data) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/baseadmin/konfession") {
                return $this->dataResponse($data);
            }
        });

        return $this;
    }

    public function fakeFailedLogin(string $mglnr): void
    {
        app(LoginFake::class)->fails($mglnr);
    }

    public function asForm(): self
    {
        return $this;
    }

    public function urlNotFoundException($url) {
        throw new \Exception('no handler found for URL '.$url);
    }

    /**
     * @param array<int, array{name: string, id: int}> $data
     */
    private function dataResponse(array $data): FulfilledPromise
    {
        $content = [
            'success' => true,
            'data' => collect($data)->map(fn ($item) => ['descriptor' => $item['name'], 'id' => $item['id'], 'name' => ''])->toArray(),
            'responseType' => 'OK',
            'totalEntries' => count ($data),
        ];

        return Http::response(json_encode($content) ?: '{}', 200);
    }

}
