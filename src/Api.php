<?php

namespace Zoomyboy\LaravelNami;

use App\Conf;
use App\Nami\Exceptions\TooManyLoginAttemptsException;
use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use Log;
use Zoomyboy\LaravelNami\Authentication\Cookie;
use Zoomyboy\LaravelNami\Backend\Backend;
use Zoomyboy\LaravelNami\Concerns\IsNamiMember;
use Zoomyboy\LaravelNami\Exceptions\RightException;
use Zoomyboy\LaravelNami\NamiException;

class Api {

    public string $url = 'https://nami.dpsg.de';
    private Cookie $cookie;

    public function __construct(Cookie $cookie)
    {
        $this->cookie = $cookie;
    }

    public function http(): PendingRequest {
        return Http::withOptions(['cookies' => $this->cookie->load()]);
    }

    public function findNr(int $nr): Member
    {
        return $this->find(['mitgliedsNummber' => $nr]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function find(array $payload): ?Member
    {
        return $this->search($payload)->first();
    }

    /**
     * @param array<string, mixed> $payload
     * @return LazyCollection<int, Member>
     */
    public function search(array $payload): LazyCollection
    {
        return LazyCollection::make(function() use ($payload) {
            $page = 1;
            while (!isset ($totalEntries) || ($page-1) * 100 + 1 <= $totalEntries) {
                $start = ($page-1) * 100;
                $url = $this->url.'/ica/rest/nami/search-multi/result-list?searchedValues='.rawurlencode(json_encode((object) $payload) ?: '{}').'&page='.$page.'&start='.$start.'&limit=100';
                $response = $this->http()->get($url);
                $totalEntries = $response->json()['totalEntries'];
                if ($response->json()['success'] !== true) {
                    $this->exception('Search failed', ['url' => $url], $response->json());
                }
                foreach ($response->json()['data'] as $member) {
                    yield Member::fromNami(collect($member)->mapWithKeys(function($value, $key) {
                        return [ str_replace('entries_', '', (string) $key) => $value ];
                    }));
                }
                $page++;
            }
        });
    }

    public function deleteMember($id) {
        $url = $this->url.'/ica/rest/nami/mitglied/filtered-for-navigation/mglschaft-beenden';
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

    public function login($mglnr = null, $password = null): self {
        if ($this->cookie->isLoggedIn()) {
            return $this;
        }

        $this->cookie->beforeLogin();

        $mglnr = $mglnr ?: config('nami.auth.mglnr');
        $password = $password ?: config('nami.auth.password');

        $this->http()->get($this->url.'/ica/pages/login.jsp');
        $response = $this->http()->asForm()->post($this->url.'/ica/rest/nami/auth/manual/sessionStartup', [
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

        $this->loggedIn = $mglnr;
        $this->cookie->afterLogin();

        return $this;
    }

    public function membersOf($groupId): Collection {
        return collect($this->http()->get($this->url.'/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/'.$groupId.'/flist')->json()['data']);
    }

    public function putMember(array $attributes) {
        $member = Member::fromAttributes($attributes);
        $existing = $this->member($member->group_id, $member->id);
        if (data_get($attributes, 'id')) {
            $payload = array_merge($existing, $member->toNami());
            $payload['kontoverbindung'] = json_encode(data_get($payload, 'kontoverbindung', []));
            $response = $this->http()->put(
                $this->url.'/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/'.$member->group_id.'/'.$member->id,
                $payload
            );
            if (data_get($response->json(), 'success') !== true) {
                $this->exception('Update failed', $member->toNami(), $response->json());
            }
        } else {
            $response = $this->http()->post($this->url.'/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/'.$member->group_id, $member->toNami());
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
            $url = $this->url."/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/{$memberId}/{$data['id']}";
            $response = $this->http()->put($url, $data);
        } else {
            $url = $this->url."/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/{$memberId}";
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
        $url = $this->url.'/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/'.$memberId.'/flist';

        $r = $this->http()->get($url);

        Logger::http($url, $r, 'Memberships from '.$memberId, ['memberId' => $memberId]);

        if (!isset($r->json()['data'])) {
            return collect([]);
        }

        return collect($r->json()['data']);
    }

    public function subactivitiesOf($activityId) {
        return collect($this->http()->get($this->url.'/ica/rest/nami/untergliederungauftaetigkeit/filtered/untergliederung/taetigkeit/'.$activityId)->json()['data'])->map(function($subactivity) {
            return Subactivity::fromNami($subactivity);
        });;
    }

    public function membership($memberId, $membershipId) {
        $url = $this->url.'/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/'.$memberId.'/'.$membershipId;
        $response = $this->http()->get($url);

        Logger::http($url, $response, 'Single Membership '.$membershipId.' from '.$memberId, ['memberId' => $memberId]);

        if($response->json()['success'] === false && Str::startsWith($response['message'], 'Sicherheitsverletzung')) {
            throw new RightException('');
        }

        return $response->json()['data'];
    }

    public function courses(): Collection
    {
        $url = $this->url.'/ica/rest/module/baustein';
        $response = $this->http()->get($url);

        return collect($response->json()['data'])->map(function($course) {
            return (object) ['name' => $course['descriptor'], 'id' => $course['id']];
        });
    }

    public function coursesFor(int $memberId): Collection
    {
        $url = $this->url."/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/flist";
        $response = $this->http()->get($url);

        if (!$response->ok() || $response->json()['success'] === false) {
            throw new RightException('Getting courses for member '.$memberId.' failed');
        }

        return collect($response->json()['data'])->map(function($course) use ($memberId) {
            $single = $this->http()->get($this->url."/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/{$course['id']}")['data'];

            return (object) [
                'id' => $single['id'],
                'organizer' => $single['veranstalter'],
                'course_id' => $single['bausteinId'],
                'event_name' => $single['vstgName'],
                'completed_at' => $single['vstgTag'],
            ];
        });
    }

    /**
     * @param int $memberId
     * @param array<string, mixed> $payload
     * @return int
     */
    public function createCourse(int $memberId, array $payload): int
    {
        $response = $this->http()->post($this->url."/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}", [
            'bausteinId' => $payload['course_id'],
            'vstgName' => $payload['event_name'],
            'vstgTag' => Carbon::parse($payload['completed_at'])->format('Y-m-d').'T00:00:00',
            'veranstalter' => $payload['organizer'],
        ]);

        if (data_get($response->json(), 'success') !== true) {
            $this->exception('Course creation failed', $payload, $response->json());
        }

        return $response['data'];
    }

    /**
     * @param int $memberId
     * @param int $courseId
     * @param array<string, mixed> $payload
     * @return void
     */
    public function updateCourse(int $memberId, int $courseId, array $payload): void
    {
        $response = $this->http()->put($this->url."/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/{$courseId}", [
            'bausteinId' => $payload['course_id'],
            'vstgName' => $payload['event_name'],
            'vstgTag' => Carbon::parse($payload['completed_at'])->format('Y-m-d').'T00:00:00',
            'veranstalter' => $payload['organizer'],
        ]);

        if (data_get($response->json(), 'success') !== true) {
            $this->exception('Course update failed', $payload, $response->json());
        }
    }

    public function deleteCourse(int $memberId, int $courseId): void
    {
        $response = $this->http()->delete($this->url."/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/{$courseId}");

        if ($response->json() !== null && data_get($response->json(), 'success') !== true) {
            $this->exception('Course deletion failed', [], $response->json());
        }
    }

    public function member($groupId, $memberId) {
        $url = $this->url.'/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/'.$groupId.'/'.$memberId;
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
        return collect($this->http()->get($this->url.'/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/'.($parentGroupId ?: 'root'))->json()['data'])->map(function($group) use ($parentGroupId) {
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
        return collect($this->http()->get($this->url."/ica/rest/baseadmin/geschlecht")['data'])->map(function($gender) {
            return Gender::fromNami($gender);
        })->filter(fn($gender) => !$gender->isNull);
    }

    public function nationalities(): Collection {
        return collect($this->http()->get($this->url."/ica/rest/baseadmin/staatsangehoerigkeit")['data'])->map(function($gender) {
            return Nationality::fromNami($gender);
        });
    }

    public function countries() {
        return collect($this->http()->get($this->url."/ica/rest/baseadmin/land")['data'])->map(function($country) {
            return Country::fromNami($country);
        });
    }

    public function regions() {
        return collect($this->http()->get($this->url."/ica/rest/baseadmin/region")['data'])->map(function($region) {
            return Region::fromNami($region);
        });
    }


    public function feesOf($groupid) {
        return collect($this->http()->get($this->url."/ica/rest/namiBeitrag/beitragsartmgl/gruppierung/{$groupid}")['data'])->map(function($fee) {
            return Fee::fromNami($fee);
        });
    }

    public function confessions(): Collection {
        return collect($this->http()->get($this->url."/ica/rest/baseadmin/konfession")['data'])->map(function($gender) {
            return Confession::fromNami($gender);
        });
    }

    public function activities($groupId) {
        return collect($this->http()->get($this->url."/ica/rest/nami/taetigkeitaufgruppierung/filtered/gruppierung/gruppierung/".$groupId)['data'])->map(function($activity) {
            return Activity::fromNami($activity);
        });
    }

    public function memberOverviewOf(int $groupId): Collection
    {
        $url = $this->url.'/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/'.$groupId.'/flist';
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
        $url = $this->url.'/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/'.$groupId.'/flist';
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

