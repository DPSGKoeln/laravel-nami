<?php

namespace Zoomyboy\LaravelNami;

use Carbon\Carbon;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use Zoomyboy\LaravelNami\Authentication\Authenticator;
use Zoomyboy\LaravelNami\Data\Activity;
use Zoomyboy\LaravelNami\Data\Group;
use Zoomyboy\LaravelNami\Data\Subactivity;
use Zoomyboy\LaravelNami\Data\Baustein;
use Zoomyboy\LaravelNami\Data\Course;
use Zoomyboy\LaravelNami\Data\Member;
use Zoomyboy\LaravelNami\Data\MemberEntry;
use Zoomyboy\LaravelNami\Data\Membership;
use Zoomyboy\LaravelNami\Data\MembershipEntry;
use Zoomyboy\LaravelNami\Exceptions\ConflictException;
use Zoomyboy\LaravelNami\Exceptions\HttpException;
use Zoomyboy\LaravelNami\Exceptions\MemberDataCorruptedException;
use Zoomyboy\LaravelNami\Exceptions\NoJsonReceivedException;
use Zoomyboy\LaravelNami\Exceptions\NotAuthenticatedException;
use Zoomyboy\LaravelNami\Exceptions\NotSuccessfulException;
use Zoomyboy\LaravelNami\Exceptions\RightException;
use Zoomyboy\LaravelNami\Support\Paginator;

class Api
{
    public string $url = 'https://nami.dpsg.de';
    private Authenticator $authenticator;

    public function __construct(Authenticator $authenticator)
    {
        $this->authenticator = $authenticator;
    }

    public function http(): PendingRequest
    {
        return $this->authenticator->http();
    }

    public function findNr(int $nr): ?MemberEntry
    {
        $this->assertLoggedIn();

        return $this->find(['mitgliedsNummber' => $nr]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function find(array $payload): ?MemberEntry
    {
        $this->assertLoggedIn();

        return $this->search($payload)->first();
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return LazyCollection<int, MemberEntry>
     */
    public function search(array $payload): LazyCollection
    {
        $this->assertLoggedIn();

        return app(Paginator::class)->startResult(100,
            fn ($page, $start) => $this->http()->get($this->url.'/ica/rest/nami/search-multi/result-list?searchedValues='.rawurlencode(json_encode((object) $payload) ?: '{}').'&page='.$page.'&start='.$start.'&limit=100'),
            function ($response) {
                if (true !== $response->json()['success']) {
                    $this->exception(NotSuccessfulException::class, 'Search failed', '', $response->json(), []);
                }
                foreach ($response->json()['data'] as $member) {
                    yield MemberEntry::from($member);
                }
            },
            fn ($response) => $response->json()['totalEntries'],
        );
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

        if (true !== $response['success']) {
            $this->exception(NotSuccessfulException::class, 'Deleting member failed', $url, $response->json(), $payload);
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

    public function putMember(Member $member, ?int $firstActivity = null, ?int $firstSubactivity = null): int
    {
        $this->assertLoggedIn();
        if ($member->id) {
            $existing = $this->rawMember($member->groupId, $member->id);
            $payload = array_merge($existing, $member->toNami());
            $payload['kontoverbindung'] = json_encode(data_get($payload, 'kontoverbindung', []));
            $url = $this->url.'/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/'.$member->groupId.'/'.$member->id;
            $response = $this->http()->put($url, $payload);
            $this->assertOk($response, $url, 'Update failed');

            return $response->json()['data']['id'];
        } else {
            $url = $this->url.'/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/'.$member->groupId;
            $response = $this->http()->post($url, [
                ...$member->toNami(),
                'ersteTaetigkeitId' => $firstActivity,
                'ersteUntergliederungId' => $firstSubactivity,
            ]);
            if (true !== data_get($response->json(), 'success')) {
                $this->exception(NotSuccessfulException::class, 'Update failed', $url, $response->json(), $member->toNami());
            }

            return $response->json()['data'];
        }
    }

    public function putMembership(int $memberId, Membership $data): int
    {
        $this->assertLoggedIn();
        if ($data->id) {
            $url = $this->url."/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/{$memberId}/{$data->id}";
            $response = $this->http()->put($url, $data->toNami());
        } else {
            $url = $this->url."/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/{$memberId}";
            $response = $this->http()->post($url, $data->toNami());
        }
        if (true !== data_get($response->json(), 'success')) {
            $defaultError = 'Erstellen der Mitgliedschaft fehlgeschlagen';
            $this->exception(NotSuccessfulException::class, data_get($response->json(), 'message', $defaultError) ?: $defaultError, $url, $response->json(), $data->toArray());
        }

        if (data_get($data, 'id')) {
            return $response->json()['data']['id'];
        } else {
            return $response->json()['data'];
        }
    }

    /**
     * @return Collection<int, MembershipEntry>
     */
    public function membershipsOf(int $memberId): Collection
    {
        $this->assertLoggedIn();

        return $this
            ->fetchCollection(
                '/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/'.$memberId.'/flist',
                'Membership fetch failed'
            )
            ->map(fn ($membership) => MembershipEntry::from($membership));
    }

    public function deleteMembership(int $memberId, Membership $membership): void
    {
        $this->assertLoggedIn();
        $url = "/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/{$memberId}/{$membership->id}";

        try {
            $this->delete($url, 'Deleting membership failed');
        } catch (NotSuccessfulException $e) {
            if (is_null($membership->id)) {
                throw new Exception('ID not given in Membership');
            }
            $membership->endsAt = today();
            $this->putMembership($memberId, $membership);
        }
    }

    /**
     * @return Collection<int, Subactivity>
     */
    public function subactivitiesOf(Activity $activity): Collection
    {
        $this->assertLoggedIn();

        return $this->fetchCollection(
            '/ica/rest/nami/untergliederungauftaetigkeit/filtered/untergliederung/taetigkeit/'.$activity->id,
            'Fetch subactivities failed'
        )->map(fn ($subactivity) => Subactivity::from($subactivity));
    }

    public function membership(int $memberId, int $membershipId): Membership
    {
        $this->assertLoggedIn();
        $membership = $this->fetchData(
            "/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/{$memberId}/{$membershipId}",
            "Fails fetching membership {$membershipId} for {$memberId}",
        );

        return Membership::from($membership);
    }

    /**
     * @return Collection<int, Baustein>
     */
    public function courses(): Collection
    {
        $this->assertLoggedIn();

        return $this->fetchCollection('/ica/rest/module/baustein', 'Fetch courses failed')
            ->map(fn ($course) => Baustein::from($course));
    }

    /**
     * @return Collection<Course>
     */
    public function coursesOf(int $memberId): Collection
    {
        $this->assertLoggedIn();

        return $this->fetchCollection("/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/flist", 'Courses fetch failed')
            ->map(fn ($course) => rescue(fn () => $this->course($memberId, $course['id']), null))
            ->filter(fn ($course) => null !== $course);
    }

    public function course(int $memberId, int $courseId): Course
    {
        $single = $this->fetchData("/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/{$courseId}", 'Error fetching single course');

        return Course::from($single);
    }

    /**
     * @param array<string, mixed> $payload
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

        if (true !== data_get($response->json(), 'success')) {
            $this->exception(NotSuccessfulException::class, 'Course creation failed', $url, $response->json(), $payload);
        }

        return $response['data'];
    }

    /**
     * @param array<string, mixed> $payload
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

        if (true !== data_get($response->json(), 'success')) {
            $this->exception(NotSuccessfulException::class, 'Course update failed', $url, $response->json(), $payload);
        }
    }

    public function deleteCourse(int $memberId, int $courseId): void
    {
        $this->assertLoggedIn();
        $url = $this->url."/ica/rest/nami/mitglied-ausbildung/filtered-for-navigation/mitglied/mitglied/{$memberId}/{$courseId}";
        $response = $this->http()->delete($url);

        if (null !== $response->json() && true !== data_get($response->json(), 'success')) {
            $this->exception(NotSuccessfulException::class, 'Course deletion failed', $url, $response->json());
        }
    }

    public function member(int $groupId, int $memberId): Member
    {
        $rawMember = $this->rawMember($groupId, $memberId);

        throw_unless(Member::isCorrupted($rawMember), MemberDataCorruptedException::class, $rawMember);

        return Member::from($rawMember);
    }

    public function hasGroup(int $groupId): bool
    {
        $this->assertLoggedIn();

        return false !== $this->groups()->search(fn ($group) => $group->id == $groupId);
    }

    /**
     * @return Collection<int, Group>
     */
    public function groups(?Group $parentGroup = null): Collection
    {
        $this->assertLoggedIn();

        return $this->fetchCollection(
            '/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/'.($parentGroup ? $parentGroup->id : 'root'),
            'Group fetch failed'
        )->map(fn ($group) => Group::from([...$group, 'parentId' => $parentGroup ? $parentGroup->id : null]));
    }

    public function group(int $groupId): ?Group
    {
        $this->assertLoggedIn();

        return $this->groups()->first(fn ($group) => $group->id == $groupId);
    }

    public function genders(): Collection
    {
        $this->assertLoggedIn();

        return $this
            ->fetchCollection('/ica/rest/baseadmin/geschlecht', 'Gender fetch failed')
            ->map(fn ($gender) => Gender::fromNami($gender))
            ->filter(fn ($gender) => !$gender->isNull);
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

    public function confessions(): Collection
    {
        $this->assertLoggedIn();

        return $this->fetchCollection('/ica/rest/baseadmin/konfession', 'Fetch confessions failed')
            ->map(fn ($confession) => Confession::fromNami($confession));
    }

    /**
     * @return Collection<int, Activity>
     */
    public function activities(Group $group): Collection
    {
        $this->assertLoggedIn();

        return $this->fetchCollection("/ica/rest/nami/taetigkeitaufgruppierung/filtered/gruppierung/gruppierung/{$group->id}", 'Fetch activities failed')
            ->map(fn ($activity) => Activity::from($activity));
    }

    /**
     * @param class-string<HttpException> $e
     * @param array<string, mixed>        $response
     * @param array<string, mixed>        $requestData
     */
    private function exception(string $e, string $message, string $url, array $response, array $requestData = []): void
    {
        throw (new $e($message))->response($response)->request($url, $requestData);
    }

    /**
     * @return array<string, mixed>
     */
    private function rawMember(int $groupId, int $memberId): array
    {
        $this->assertLoggedIn();

        return $this->fetchData(
            '/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/'.$groupId.'/'.$memberId,
            'Fetch von Mitglied '.$memberId.' in Gruppe '.$groupId.' fehlgeschlagen'
        );
    }

    private function assertLoggedIn(): void
    {
        $this->authenticator->refresh();

        if (!$this->isLoggedIn()) {
            throw new NotAuthenticatedException('You need to login first');
        }
    }

    /**
     * @return Collection<int, mixed>
     */
    private function fetchCollection(string $url, string $error): Collection
    {
        $response = $this->http()->get($this->url.$url);

        $this->assertOk($response, $url, $error);
        /** @var array<int, mixed> */
        $data = $response['data'];

        return collect($data);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchData(string $url, string $error): array
    {
        $response = $this->http()->get($this->url.$url);

        $this->assertOk($response, $url, $error);

        return $response->json()['data'];
    }

    private function delete(string $url, string $error): void
    {
        $response = $this->http()->withHeaders([
            'content-type' => 'application/json',
            'accept' => 'application/json',
        ])->delete($this->url.$url);

        $this->assertOk($response, $url, $error);
    }

    private function assertOk(Response $response, string $url, string $error): void
    {
        if (null === $response->json()) {
            $this->exception(NoJsonReceivedException::class, $error, $url, []);
        }

        if (data_get($response, 'message') && Str::contains($response['message'], 'no right')) {
            $this->exception(RightException::class, $error, $url, $response->json());
        }

        if (data_get($response, 'message') && Str::contains($response['message'], 'Sicherheitsverletzung')) {
            $this->exception(RightException::class, $error, $url, $response->json());
        }

        if (data_get($response, 'message') && Str::contains($response['message'], 'Der Datensatz wurde zwischenzeitlich verändert')) {
            $this->exception(ConflictException::class, $error, $url, $response->json());
        }

        if (false === $response['success']) {
            $this->exception(NotSuccessfulException::class, $error, $url, $response->json());
        }
    }
}
