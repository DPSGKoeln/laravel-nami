<?php

namespace Zoomyboy\LaravelNami\Backend;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class FakeBackend {

    private $members;
    private $passwords;
    public $groups = [];
    public $loggedInAs = null;

    public function __construct() {
        $this->members = collect([]);
    }

    public function addMember(array $member) {
        $member['mitgliedsNummer'] = $member['id'];
        $this->members->push($member);
    }

    public function init($cookie) {
        $this->cookie = $cookie;
        return $this;
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

    public function post($url, $data) {
        if ($url === 'https://nami.dpsg.de/ica/rest/nami/auth/manual/sessionStartup') {
            if (!data_get($data, 'username') || !data_get($data, 'password')) {
                return $this->wrongLoginResponse();
            }

            if ($this->passwords[data_get($data, 'username')] === data_get($data, 'password')) {
                $this->loggedInAs = data_get($data, 'username');
                $this->cookie->set($data['username'], 'rZMBv1McDAJ-KukQ6BboJBTq.srv-nami06');
                return $this->response([
                    "statusCode" => 0,
                ]);
            }
        }

        $this->urlNotFoundException($url);
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

    private function notAuthorizedResponse() {
        return $this->response([
            'success' => true,
            'data' => null,
            'responseType' => 'ERROR',
            'message' => 'Session expired',
            'title' => 'Exception',
        ]);
    }

    public function response($data) {
        return new Response(new GuzzleResponse(200, [], json_encode($data)));
    }

    public function fakeNamiPassword($mglnr, $password, $groups) {
        $this->passwords[$mglnr] = $password;
        $this->groups[$mglnr] = $groups;
    }

    public function asForm() {
        return $this;
    }

    public function urlNotFoundException($url) {
        throw new \Exception('no handler found for URL '.$url);
    }

}
