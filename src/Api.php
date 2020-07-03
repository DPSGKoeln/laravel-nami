<?php

namespace Zoomyboy\LaravelNami;

use Log;
use App\Conf;
use Illuminate\Support\Str;
use App\Nami\Exceptions\TooManyLoginAttemptsException;
use App\Nami\Interfaces\UserResolver;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;

class Api {

    public $cookie;
    public $loggedIn = null;
    public static $url = 'https://nami.dpsg.de';

    public function __construct() {
        $this->cookie = new \GuzzleHttp\Cookie\CookieJar();
    }

    private function http() {
        return Http::withOptions(['cookies' => $this->cookie]);
    }

    public function setUser(NamiUser $user) {
        $this->user = $user;

        return $this;
    }

    protected function loggedInAlready(): bool {
        return $this->loggedIn !== null;
    }

    public function login($mglnr = null, $password = null, $groupid = null): self {
        if ($this->loggedIn) { return $this; }

        $mglnr = $mglnr ?: config('nami.auth.mglnr');
        $password = $password ?: config('nami.auth.password');
        $groupid = $groupid ?: config('nami.auth.groupid');

        Http::withOptions(['cookies' => $this->cookie])->get(self::$url.'/ica/pages/login.jsp');
        $response = Http::asForm()->withOptions(['cookies' => $this->cookie])->post(self::$url.'/ica/rest/nami/auth/manual/sessionStartup', [
            'Login' => 'API',
            'redirectTo' => './app.jsp',
            'username' => $mglnr,
            'password' => $password
        ])->json();

        if ($response['statusCode'] !== 0) {
            $e = new LoginException();
            $e->setResponse($response);
            throw $e;
        }

        $this->loggedIn = $mglnr;

        return $this;
    }

    public function memberModel($member): Model {
        $eloquentClass = config('nami.models.member');
        return $eloquentClass::findByNamiId($member['id']) ?: $this->fillFromOverview(new $eloquentClass);
    }

    public function membersOf($groupId): Collection {
        return collect($this->http()->get(self::$url.'/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/'.$groupId.'/flist')->json()['data']);
    }

    public function member($groupId, $memberId) {
        $url = self::$url.'/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/'.$groupId.'/'.$memberId;
        $response = $this->http()->get($url);

        Log::debug('Member Request '.$memberId, [
            'url' => $url,
            'response' => $response->body(),
            'json' => $response->json()
        ]);

        if ($response->json()['success'] === true) {
            return $response->json()['data'];
        }


        if(Str::startsWith($response['message'], 'Access denied')) {
            return $this->singleMemberFallback($groupId, $memberId);
        }

        return $response->json()['data'];
    }

    public function hasGroup($groupId): bool {
        return $this->groups()->search(function($group) use ($groupId) {
            return $group->id == $groupId;
        }) !== false;
    }

    public function groups($parentGroupId = null): Collection {
        $parentGroupId = $parentGroupId ?: 'root';
        return collect($this->http()->get(self::$url.'/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/'.$parentGroupId)->json()['data'])->map(function($group) {
            return Group::fromResponse($group);
        });
    }

    public function group($groupId) {
        return $this->groups()->first(function($group) use ($groupId) {
            return $group->id == $groupId;
        });
    }

    public function subgroupsOf($groupId) {
        return $this->groups($groupId);
    }

    public function genders(): Collection {
        return collect($this->http()->get(self::$url."/ica/rest/baseadmin/geschlecht")['data'])->map(function($gender) {
            return Gender::fromNami($gender);
        });
    }

    public function nationalities(): Collection {
        return collect($this->http()->get(self::$url."/ica/rest/baseadmin/staatsangehoerigkeit")['data'])->map(function($gender) {
            return Nationality::fromNami($gender);
        });
    }

    public function confessions(): Collection {
        return collect($this->http()->get(self::$url."/ica/rest/baseadmin/konfession")['data'])->map(function($gender) {
            return Confession::fromNami($gender);
        });
    }

    private function singleMemberFallback($groupId, $memberId) {
        $url = self::$url.'/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/'.$groupId.'/flist';
        $response = $this->http()->get($url);

        $member = collect($response['data'])->first(function($member) use ($memberId) {
            return $member['id'] == $memberId;
        });
        
        $member = collect($member)->mapWithKeys(function($value, $key) {
            return [ str_replace('entries_', '', $key) => $value ];
        });

        return $member->toArray();
    }

    // -------------------------------------

    public function fees() {
        $response = $this->client->get("/ica/rest/namiBeitrag/beitragsartmgl/gruppierung/{$this->user->getNamiGroupId()}", [
            'cookies' => $this->cookie
        ]);

        return json_decode((string)$response->getBody());
    }

    public function regions() {
        $response = $this->client->get("/ica/rest/baseadmin/region", [
            'cookies' => $this->cookie
        ]);

        return json_decode((string)$response->getBody());
    }

    public function countries() {
        $response = $this->client->get("/ica/rest/baseadmin/land", [
            'cookies' => $this->cookie
        ]);

        return json_decode((string)$response->getBody());
    }

    public function activities() {
        $response = $this->client->get("/ica/rest/nami/taetigkeitaufgruppierung/filtered/gruppierung/gruppierung/{$this->user->getNamiGroupId()}", [
            'cookies' => $this->cookie
        ]);

        return json_decode((string)$response->getBody());
    }

    public function groupForActivity($activityId) {

        $response = $this->client->get("/ica/rest//nami/untergliederungauftaetigkeit/filtered/untergliederung/taetigkeit/{$activityId}", [
            'cookies' => $this->cookie
        ]);

        return json_decode((string)$response->getBody());
    }

    public function allMembers() {
        $response = $this->client->get("/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/{$this->user->getNamiGroupId()}/flist", [
            'cookies' => $this->cookie
        ]);

        return json_decode((string)$response->getBody());
    }

    public function getMember($memberId) {
        $response = $this->client->get('/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/'.$this->user->getNamiGroupId().'/'.$memberId, [
                'cookies' => $this->cookie,
                'http_errors' => false
            ]
        );

        return json_decode((string)$response->getBody());
    }

    /** @todo testen mit guzzle fake */
    public function isSuccess($response) {
        return isset ($response->success) && $response->success === true
            && isset ($response->responseType) && $response->responseType == 'OK';
    }

    public function checkCredentials() {
        try {
            $this->login();
        } catch (LoginException $e) {
            return false;
        }

        return true;
    }

    public function get($url) {
        $this->login();

        $response = $this->client->request('GET', $this->baseUrl.$url, [
            'http_errors' => false,
            'cookies' => $this->cookie
        ]);

        $json = json_decode((string) $response->getBody());

        return collect($json);
    }

    public function post($url, $fields) {
        $this->login();

        $response = $this->client->request('POST', $this->baseUrl.$url, [
            'http_errors' => false,
            'cookies' => $this->cookie,
            'headers' => [
                'Accept' => '*/*',
                'Content-Type' => 'application/x-www-form-urlencoded; charset=UTF-8'
            ],
            'form_params' => $fields
        ]);

        $json = json_decode((string) $response->getBody());

        return collect($json);
    }

    public function put($url, $fields) {
        $this->login();

        $response = $this->client->request('PUT', $this->baseUrl.$url, [
            'http_errors' => false,
            'cookies' => $this->cookie,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ],
            'json' => $fields
        ]);

        $json = json_decode((string) $response->getBody());

        if (is_null($json)) {
            \Log::critical('Api gibt kein JSON zurück', [
                'response' => (string) $response->getBody(),
                'fields' => $fields,
                'url' => $url
            ]);

            return null;
        }

        if (!$json->success || $json->success == false) {
            \Log::critical('Fehler beim Update', [
                'response' => (string) $response->getBody(),
                'fields' => $fields,
                'url' => $url
            ]);

            return null;
        }

        return collect($json);
    }
}
