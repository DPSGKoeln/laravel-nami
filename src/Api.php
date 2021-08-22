<?php

namespace Zoomyboy\LaravelNami;

use App\Conf;
use App\Nami\Exceptions\TooManyLoginAttemptsException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Log;
use Zoomyboy\LaravelNami\Backend\Backend;
use Zoomyboy\LaravelNami\Concerns\IsNamiMember;
use Zoomyboy\LaravelNami\Cookies\Cookie;
use Zoomyboy\LaravelNami\Exceptions\RightException;
use Zoomyboy\LaravelNami\NamiException;

class Api {

    private $cookie;
    public $loggedIn = null;
    public static $url = 'https://nami.dpsg.de';

    public function __construct($cookieStore) {
        $this->cookie = $cookieStore;
    }

    public function http() {
        return Backend::init($this->cookie);
    }

    public function findNr($nr) {
        return $this->search(['mitgliedsNummber' => $nr]);
    }

    public function search($payload) {
        $url = self::$url.'/ica/rest/nami/search-multi/result-list?searchedValues='.rawurlencode(json_encode($payload)).'&page=1&start=0&limit=10';
        $response = $this->http()->get($url);

        if ($response->json()['success'] === true) {

            if (!count($response->json()['data'])) {
                return null;
            }
            $data = collect($response->json()['data'][0])->mapWithKeys(function($value, $key) {
                return [ str_replace('entries_', '', $key) => $value ];
            });

            return Member::fromNami($data);
        }

        $this->exception('Search failed', ['url' => $url], $response->json());
    }

    protected function loggedInAlready(): bool {
        return $this->loggedIn !== null;
    }

    public function deleteMember($id) {
        $url = self::$url.'/ica/rest/nami/mitglied/filtered-for-navigation/mglschaft-beenden';
        $payload = [
            'id' => $id,
            'isConfirmed' => 'true',
            'beendenZumDatum' => now()->subDays(1)->format('Y-m-d').' 00:00:00',
        ];
        $response = $this->http()->asForm()->post($url, $payload);

        if ($response['success'] !== true) {
            $this->exception('Deleting member failed', ['url' => $url, 'post' => $payload], $response->json());
        }
    }

    public function login($mglnr = null, $password = null, $groupid = null): self {
        $resolved = $this->cookie->resolve($mglnr);

        if ($resolved && !$this->cookie->isExpired()) {
            return $this;
        }

        $mglnr = $mglnr ?: config('nami.auth.mglnr');
        $password = $password ?: config('nami.auth.password');
        $groupid = $groupid ?: config('nami.auth.groupid');

        $this->http()->get(self::$url.'/ica/pages/login.jsp');
        $response = $this->http()->asForm()->post(self::$url.'/ica/rest/nami/auth/manual/sessionStartup', [
            'Login' => 'API',
            'redirectTo' => './app.jsp',
            'username' => $mglnr,
            'password' => $password
        ]);

        if ($response->json()['statusCode'] !== 0) {
            $e = new LoginException();
            $e->setResponse($response->json());
            throw $e;
        }

        $this->cookie->store($mglnr);
        $this->loggedIn = $mglnr;

        return $this;
    }

    public function membersOf($groupId): Collection {
        return collect($this->http()->get(self::$url.'/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/'.$groupId.'/flist')->json()['data']);
    }

    public function putMember(array $attributes) {
        $member = Member::fromAttributes($attributes);
        if (data_get($attributes, 'id')) {
            $response = $this->http()->put(self::$url.'/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/'.$member->group_id.'/'.$member->id, $member->toNami());
            if (data_get($response->json(), 'success') !== true) {
                $this->exception('Update failed', $member->toNami(), $response->json());
            }
        } else {
            $response = $this->http()->post(self::$url.'/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/'.$member->group_id, $member->toNami());
            if (data_get($response->json(), 'success') !== true) {
                $this->exception('Update failed', $member->toNami(), $response->json());
            }

            return ['id' => $response->json()['data']];
        }

        return $response->json()['data'];
    }

    public function putMembership(int $memberId, array $data): int
    {
        if (data_get($data, 'id')) {
            $url = self::$url."/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/{$memberId}/{$data['id']}";
            $response = $this->http()->put($url, $data);
        } else {
            $url = self::$url."/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/{$memberId}";
            $response = $this->http()->post($url, $data);
        }
        if (data_get($response->json(), 'success') !== true) {
            $this->exception('Update failed', ['url' => $url, 'data' => $data], $response->json());
        }

        if (data_get($data, 'id')) {
            return $response->json()['data']['id'];
        } else {
            return $response->json()['data'];
        }
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

        if($response->json()['success'] === false && Str::startsWith($response['message'], 'Sicherheitsverletzung')) {
            throw new RightException('');
        }

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

    private function exception($message, $request, $response) {
        throw (new NamiException($message))->response($response)->request($request);
    }

}

