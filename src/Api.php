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
use Zoomyboy\LaravelNami\Authentication\Authenticator;
use Zoomyboy\LaravelNami\Concerns\IsNamiMember;
use Zoomyboy\LaravelNami\Exceptions\NotAuthenticatedException;
use Zoomyboy\LaravelNami\Exceptions\RightException;
use Zoomyboy\LaravelNami\NamiException;

class Api {

    public string $url = 'https://nami.dpsg.de';
    private Authenticator $authenticator;

    public function __construct(Authenticator $authenticator)
    {
        $this->authenticator = $authenticator;
    }

    public function http(): PendingRequest {
        return $this->authenticator->http();
    }

    public function findNr(int $nr): Member
    {
        $this->assertLoggedIn();
        return $this->find(['mitgliedsNummber' => $nr]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function find(array $payload): ?Member
    {
        $this->assertLoggedIn();
        return $this->search($payload)->first();
    }

    /**
     * @param array<string, mixed> $payload
     * @return LazyCollection<int, Member>
     */
    public function search(array $payload): LazyCollection
    {
        $this->assertLoggedIn();
        return LazyCollection::make(function() use ($payload) {
            $page = 1;
            while (!isset ($totalEntries) || ($page-1) * 100 + 1 <= $totalEntries) {
                $start = ($page-1) * 100;
                $url = $this->url.'/ica/rest/nami/search-multi/result-list?searchedValues='.rawurlencode(json_encode((object) $payload) ?: '{}').'&page='.$page.'&start='.$start.'&limit=100';
                $response = $this->http()->get($url);
                if ($response->json()['success'] !== true) {
                    $this->exception('Search failed', $url, $response->json(), ['page' => $page, 'start' => $start]);
                }
                $totalEntries = $response->json()['totalEntries'];
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
        $this->assertLoggedIn();
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

    public function login(int $mglnr, string $password): self
    {
        $this->authenticator->login($mglnr, $password);

        return $this;
    }

    public function isLoggedIn(): bool
    {
        return $this->authenticator->isLoggedIn();
    }

    public function membersOf($groupId): Collection {
        $this->assertLoggedIn();
        return collect($this->http()->get($this->url.'/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/'.$groupId.'/flist')->json()['data']);
    }

    public function putMember(array $attributes) {
        $this->assertLoggedIn();
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
        $this->assertLoggedIn();
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
        $this->assertLoggedIn();
        $url = $this->url.'/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/'.$memberId.'/flist';

        $r = $this->http()->get($url);

        Logger::http($url, $r, 'Memberships from '.$memberId, ['memberId' => $memberId]);

        if (!isset($r->json()['data'])) {
            return collect([]);
        }

        return collect($r->json()['data']);
    }

    public function subactivitiesOf(int $activityId): Collection
    {
        $this->assertLoggedIn();
        $url = $this->url.'/ica/rest/nami/untergliederungauftaetigkeit/filtered/untergliederung/taetigkeit/'.$activityId;
        $response = $this->http()->get($url);

        if ($response['success'] === false) {
            $this->exception('Getting subactivities failed', $url, $response->json());
        }

        return collect($response['data'])->map(function($subactivity) {
            return Subactivity::fromNami($subactivity);
        });
    }

    public function membership($memberId, $membershipId) {
        $this->assertLoggedIn();
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
        $this->assertLoggedIn();
        $url = $this->url.'/ica/rest/module/baustein';
        $response = $this->http()->get($url);

        return collect($response->json()['data'])->map(function($course) {
            return (object) ['name' => $course['descriptor'], 'id' => $course['id']];
        });
    }

    public function coursesFor(int $memberId): Collection
    {
        $this->assertLoggedIn();
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
        $this->assertLoggedIn();
        $url = $this->url."/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}";
        $payload = [
            'bausteinId' => $payload['course_id'],
            'vstgName' => $payload['event_name'],
            'vstgTag' => Carbon::parse($payload['completed_at'])->format('Y-m-d').'T00:00:00',
            'veranstalter' => $payload['organizer'],
        ];
        $response = $this->http()->post($url, $payload);

        if (data_get($response->json(), 'success') !== true) {
            $this->exception('Course creation failed', $url, $response->json(), $payload);
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
        $this->assertLoggedIn();
        $url = $this->url."/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/{$courseId}";
        $payload = [
            'bausteinId' => $payload['course_id'],
            'vstgName' => $payload['event_name'],
            'vstgTag' => Carbon::parse($payload['completed_at'])->format('Y-m-d').'T00:00:00',
            'veranstalter' => $payload['organizer'],
        ];
        $response = $this->http()->put($url, $payload);

        if (data_get($response->json(), 'success') !== true) {
            $this->exception('Course update failed', $url, $response->json(), $payload);
        }
    }

    public function deleteCourse(int $memberId, int $courseId): void
    {
        $this->assertLoggedIn();
        $url = $this->url."/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/{$courseId}";
        $response = $this->http()->delete($url);

        if ($response->json() !== null && data_get($response->json(), 'success') !== true) {
            $this->exception('Course deletion failed', $url, $response->json());
        }
    }

    public function member($groupId, $memberId) {
        $this->assertLoggedIn();
        $url = $this->url.'/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/'.$groupId.'/'.$memberId;
        $response = $this->http()->get($url);

        Logger::http($url, $response, 'Show member '.$memberId, ['memberId' => $memberId]);

        if($response->json()['success'] === false && Str::startsWith($response['message'], 'Access denied')) {
            return $this->singleMemberFallback($groupId, $memberId);
        }

        if($response->json()['success'] === false && Str::startsWith($response['message'], 'Sicherheitsverletzung: Zugriff')) {
            return $this->singleMemberFallback($groupId, $memberId);
        }

        if ($response->json()['success'] !== true) {
            $this->exception('Fetching member failed', $url, $response->json());
        }

        return $response->json()['data'];
    }

    public function hasGroup($groupId): bool {
        $this->assertLoggedIn();
        return $this->groups()->search(function($group) use ($groupId) {
            return $group->id == $groupId;
        }) !== false;
    }

    public function groups($parentGroupId = null): Collection {
        $this->assertLoggedIn();
        return collect($this->http()->get($this->url.'/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/'.($parentGroupId ?: 'root'))->json()['data'])->map(function($group) use ($parentGroupId) {
            return Group::fromResponse($group, $parentGroupId);
        });
    }

    public function group($groupId): Group {
        $this->assertLoggedIn();
        return $this->groups()->first(function($group) use ($groupId) {
            return $group->id == $groupId;
        });
    }

    public function subgroupsOf($groupId) {
        $this->assertLoggedIn();
        return $this->groups($groupId);
    }

    public function genders(): Collection {
        $this->assertLoggedIn();
        return collect($this->http()->get($this->url."/ica/rest/baseadmin/geschlecht")['data'])->map(function($gender) {
            return Gender::fromNami($gender);
        })->filter(fn($gender) => !$gender->isNull);
    }

    public function nationalities(): Collection {
        $this->assertLoggedIn();
        $url = $this->url."/ica/rest/baseadmin/staatsangehoerigkeit";
        $response = $this->http()->get($url);

        if ($response->json()['success'] !== true) {
            $this->exception("Fetch von Nationalität fehlgeschlagen", $url, $response->json());
        }

        return collect($response['data'])->map(function($nationality) {
            return Nationality::fromNami($nationality);
        });
    }

    public function countries() {
        $this->assertLoggedIn();
        return collect($this->http()->get($this->url."/ica/rest/baseadmin/land")['data'])->map(function($country) {
            return Country::fromNami($country);
        });
    }

    public function regions() {
        $this->assertLoggedIn();
        return collect($this->http()->get($this->url."/ica/rest/baseadmin/region")['data'])->map(function($region) {
            return Region::fromNami($region);
        });
    }


    public function feesOf($groupid) {
        $this->assertLoggedIn();
        return collect($this->http()->get($this->url."/ica/rest/namiBeitrag/beitragsartmgl/gruppierung/{$groupid}")['data'])->map(function($fee) {
            return Fee::fromNami($fee);
        });
    }

    public function confessions(): Collection {
        $this->assertLoggedIn();
        return collect($this->http()->get($this->url."/ica/rest/baseadmin/konfession")['data'])->map(function($gender) {
            return Confession::fromNami($gender);
        });
    }

    public function activities($groupId) {
        $this->assertLoggedIn();
        return collect($this->http()->get($this->url."/ica/rest/nami/taetigkeitaufgruppierung/filtered/gruppierung/gruppierung/".$groupId)['data'])->map(function($activity) {
            return Activity::fromNami($activity);
        });
    }

    public function memberOverviewOf(int $groupId): Collection
    {
        $this->assertLoggedIn();
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
        $this->assertLoggedIn();
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

    private function exception(string $message, string $url, array $response, array $requestData = []): void
    {
        throw (new NamiException($message))->response($response)->request($url, $requestData);
    }

    private function assertLoggedIn(): void
    {
        if (!$this->isLoggedIn()) {
            throw new NotAuthenticatedException('You need to login first');
        }
    }

}

