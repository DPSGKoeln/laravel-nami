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
use Zoomyboy\LaravelNami\Data\Baustein;
use Zoomyboy\LaravelNami\Data\Course;
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

    public function deleteMember(int $id): void
    {
        $this->assertLoggedIn();
        $url = $this->url.'/ica/rest/nami/mitglied/filtered-for-navigation/mglschaft-beenden';
        $payload = [
            'id' => $id,
            'isConfirmed' => 'true',
            'beendenZumDatum' => now()->subDays(1)->format('Y-m-d').' 00:00:00',
        ];
        $response = $this->http()->asForm()->post($url, $payload);

        if ($response['success'] !== true) {
            $this->exception('Deleting member failed', $url, $response->json(), $payload);
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

    public function membersOf(int $groupId): Collection
    {
        $this->assertLoggedIn();
        return $this->fetchCollection(
            '/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/'.$groupId.'/flist',
            'Member fetch failed'
        );
    }

    public function putMember(array $attributes): array
    {
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

    public function membershipsOf(int $memberId): Collection
    {
        $this->assertLoggedIn();
        
        return $this->fetchCollection(
            '/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/'.$memberId.'/flist',
            'Membership fetch failed'
        );
    }

    public function subactivitiesOf(int $activityId): Collection
    {
        $this->assertLoggedIn();

        return $this->fetchCollection(
            '/ica/rest/nami/untergliederungauftaetigkeit/filtered/untergliederung/taetigkeit/'.$activityId,
            'Fetch subactivities failed'
        )->map(fn ($subactivity) => Subactivity::fromNami($subactivity));
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

        return $this->fetchCollection('/ica/rest/module/baustein', 'Fetch courses failed')
            ->map(fn ($course) => new Baustein($course));
    }

    /**
     * @return Collection<Course>
     */
    public function coursesFor(int $memberId): Collection
    {
        $this->assertLoggedIn();

        return $this->fetchCollection("/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/flist", 'Courses fetch failed')
            ->map(fn ($course) => $this->course($memberId, $course['id']))
            ->filter(fn ($course) => $course !== null);
    }

    public function course(int $memberId, int $courseId): ?Course
    {
        $single = $this->fetchData("/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/{$courseId}", "Error fetching single course");

        return $single ? new Course($single) : null;
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

    public function hasGroup(int $groupId): bool
    {
        $this->assertLoggedIn();
        return $this->groups()->search(fn ($group) => $group->id == $groupId) !== false;
    }

    public function groups(int $parentGroupId = null): Collection
    {
        $this->assertLoggedIn();
       
        return $this->fetchCollection(
            '/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/'.($parentGroupId ?: 'root'),
            'Group fetch failed'
        )->map(fn ($group) => Group::fromResponse($group, $parentGroupId));
    }

    public function group(int $groupId): ?Group {
        $this->assertLoggedIn();

        return $this->groups()->first(fn ($group) => $group->id == $groupId);
    }

    public function subgroupsOf(int $groupId): Collection
    {
        $this->assertLoggedIn();
        return $this->groups($groupId);
    }

    public function genders(): Collection
    {
        $this->assertLoggedIn();

        return $this
            ->fetchCollection('/ica/rest/baseadmin/geschlecht', 'Gender fetch failed')
            ->map(fn($gender) => Gender::fromNami($gender))
            ->filter(fn($gender) => !$gender->isNull);
    }

    public function nationalities(): Collection
    {
        $this->assertLoggedIn();

        return $this->fetchCollection('/ica/rest/baseadmin/staatsangehoerigkeit', 'Fetch nationalities failed')
            ->map(fn ($nationality) => Nationality::fromNami($nationality));
    }

    public function countries(): Collection
    {
        $this->assertLoggedIn();

        return $this->fetchCollection('/ica/rest/baseadmin/land', 'Fetch countries failed')
            ->map(fn ($country) => Country::fromNami($country));
    }

    public function regions(): Collection
    {
        $this->assertLoggedIn();

        return $this->fetchCollection('/ica/rest/baseadmin/region', 'Fetch regions failed')
            ->map(fn ($region) => Region::fromNami($region));
    }


    public function feesOf(int $groupid): Collection
    {
        $this->assertLoggedIn();

        return $this->fetchCollection("/ica/rest/namiBeitrag/beitragsartmgl/gruppierung/{$groupid}", 'Fetch fees failed')
            ->map(fn ($fee) => Fee::fromNami($fee));
    }

    public function confessions(): Collection {
        $this->assertLoggedIn();

        return $this->fetchCollection("/ica/rest/baseadmin/konfession", 'Fetch confessions failed')
            ->map(fn ($confession) => Confession::fromNami($confession));
    }

    public function activities($groupId) {
        $this->assertLoggedIn();

        return $this->fetchCollection("/ica/rest/nami/taetigkeitaufgruppierung/filtered/gruppierung/gruppierung/{$groupId}", 'Fetch activities failed')
            ->map(fn ($activity) => Activity::fromNami($activity));
    }

    public function memberOverviewOf(int $groupId): Collection
    {
        $this->assertLoggedIn();
        return $this->fetchCollection('/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/'.$groupId.'/flist', 'Fetch membership overview failed')
            ->map(function($member) use ($groupId) {
                $member = collect($member)->mapWithKeys(function($value, $key) {
                    return [ str_replace('entries_', '', $key) => $value ];
                });
                $member['gruppierungId'] = $groupId;

                return $member;
            });
    }

    private function singleMemberFallback(int $groupId, int $memberId): array
    {
        $this->assertLoggedIn();

        $member = $this->fetchCollection('/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/'.$groupId.'/flist', 'Fetch single member fallback failed')->first(function($member) use ($memberId) {
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
        $this->authenticator->refresh();

        if (!$this->isLoggedIn()) {
            throw new NotAuthenticatedException('You need to login first');
        }
    }

    private function fetchCollection(string $url, string $error): Collection
    {
        $response = $this->http()->get($this->url.$url);

        if ($response->json() === null) {
            return collect([]);
        }

        if (data_get($response, 'message') && Str::contains($response['message'], 'no right')) {
            return collect([]);
        }

        if (data_get($response, 'message') && Str::contains($response['message'], 'Sicherheitsverletzung')) {
            return collect([]);
        }

        if ($response['success'] === false) {
            $this->exception($error, $url, $response->json());
        }

        return collect($response['data']);
    }

    private function fetchData(string $url, string $error): ?array
    {
        $response = $this->http()->get($this->url.$url);

        if ($response->json() === null) {
            return null;
        }

        if (data_get($response, 'message') && Str::contains($response['message'], 'no right')) {
            return null;
        }

        if (data_get($response, 'message') && Str::contains($response['message'], 'Sicherheitsverletzung')) {
            return null;
        }

        if ($response['success'] === false) {
            $this->exception($error, $url, $response->json());
        }

        return $response['data'];
    }

}

