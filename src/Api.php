<?php

namespace Zoomyboy\LaravelNami;

use Log;
use App\Conf;
use Illuminate\Support\Str;
use App\Nami\Exceptions\TooManyLoginAttemptsException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Collection;
use Zoomyboy\LaravelNami\Concerns\NamiData;

class Api implements NamiData {

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

    public function putMember(Member $member) {
        $this->http()->put(self::$url.'/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/'.$member->group_id.'/'.$member->id, [
            'vorname' => $member->firstname,
            'nachname' => $member->lastname,
            'spitzname' => $member->nickname ?: '',
            'geschlechtId' => $member->gender_id ?: Gender::getNullValue(),
        ]);
    }

    public function membershipsOf($memberId): Collection {
        $url = self::$url.'/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/'.$memberId.'/flist';

        $r = $this->http()->get($url);

        Logger::http($url, $r, 'Memberships from '.$memberId, ['memberId' => $memberId]);

        if (!isset($r->json()['data'])) {
            return collect([]);
        }

        return collect($r->json()['data']);
    }

    public function subactivitiesOf($activityId) {
        return collect($this->http()->get(self::$url.'/ica/rest/nami/untergliederungauftaetigkeit/filtered/untergliederung/taetigkeit/'.$activityId)->json()['data'])->map(function($subactivity) {
            return Subactivity::fromNami($subactivity);
        });;
    }

    public function membership($memberId, $membershipId) {
        $url = self::$url.'/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/'.$memberId.'/'.$membershipId;
        $response = $this->http()->get($url);

        Logger::http($url, $response, 'Single Membership '.$membershipId.' from '.$memberId, ['memberId' => $memberId]);

        return $response->json()['data'];
    }

    public function member($groupId, $memberId) {
        $url = self::$url.'/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/'.$groupId.'/'.$memberId;
        $response = $this->http()->get($url);

        Logger::http($url, $response, 'Show member '.$memberId, ['memberId' => $memberId]);

        if($response->json()['success'] === false && Str::startsWith($response['message'], 'Access denied')) {
            return $this->singleMemberFallback($groupId, $memberId);
        }

        if($response->json()['success'] === false && Str::startsWith($response['message'], 'Sicherheitsverletzung: Zugriff')) {
            return $this->singleMemberFallback($groupId, $memberId);
        }

        if ($response->json()['success'] === true) {
            return $response->json()['data'];
        } else {
            $e = new NamiException('Fetch von Mitglied fehlgeschlagen');
            $e->setData([
                'response' => $response->body(),
                'url' => $url
            ]);
            throw $e;
        }
    }

    public function hasGroup($groupId): bool {
        return $this->groups()->search(function($group) use ($groupId) {
            return $group->id == $groupId;
        }) !== false;
    }

    public function groups($parentGroupId = null): Collection {
        return collect($this->http()->get(self::$url.'/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/'.($parentGroupId ?: 'root'))->json()['data'])->map(function($group) use ($parentGroupId) {
            return Group::fromResponse($group, $parentGroupId);
        });
    }

    public function group($groupId): Group {
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
        })->filter(fn($gender) => !$gender->isNull);
    }

    public function nationalities(): Collection {
        return collect($this->http()->get(self::$url."/ica/rest/baseadmin/staatsangehoerigkeit")['data'])->map(function($gender) {
            return Nationality::fromNami($gender);
        });
    }

    public function countries() {
        return collect($this->http()->get(self::$url."/ica/rest/baseadmin/land")['data'])->map(function($country) {
            return Country::fromNami($country);
        });
    }

    public function regions() {
        return collect($this->http()->get(self::$url."/ica/rest/baseadmin/region")['data'])->map(function($region) {
            return Region::fromNami($region);
        });
    }


    public function feesOf($groupid) {
        return collect($this->http()->get(self::$url."/ica/rest/namiBeitrag/beitragsartmgl/gruppierung/{$groupid}")['data'])->map(function($fee) {
            return Fee::fromNami($fee);
        });
    }

    public function confessions(): Collection {
        return collect($this->http()->get(self::$url."/ica/rest/baseadmin/konfession")['data'])->map(function($gender) {
            return Confession::fromNami($gender);
        });
    }

    public function activities($groupId) {
        return collect($this->http()->get(self::$url."/ica/rest/nami/taetigkeitaufgruppierung/filtered/gruppierung/gruppierung/".$groupId)['data'])->map(function($activity) {
            return Activity::fromNami($activity);
        });
    }

    public function memberOverviewOf($groupId) {
        $url = self::$url.'/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/'.$groupId.'/flist';
        $response = $this->http()->get($url);

        return collect($response['data'])->map(function($member) use ($groupId) {
            $member = collect($member)->mapWithKeys(function($value, $key) {
                return [ str_replace('entries_', '', $key) => $value ];
            });
            $member['gruppierungId'] = $groupId;

            return $member;
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
        $member['gruppierungId'] = $groupId;

        return $member->toArray();
    }

    // -------------------------------------

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
