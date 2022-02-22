<?php

namespace Zoomyboy\LaravelNami\Backend;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Zoomyboy\LaravelNami\Fakes\CourseFake;
use Zoomyboy\LaravelNami\Fakes\Fake;
use Zoomyboy\LaravelNami\Fakes\FakeInstance;
use Zoomyboy\LaravelNami\Fakes\LoginFake;

class FakeBackend {

    /**
     * @param int $mitgliedsNr
     * @param array <string, mixed> $data
     */
    public function addSearch(int $mitgliedsNr, array $data): self
    {
        Http::fake(function($request) use ($data, $mitgliedsNr) {
            if ($request->url() === 'https://nami.dpsg.de/ica/rest/nami/search-multi/result-list?searchedValues='.rawurlencode(json_encode(['mitgliedsNummber' => $mitgliedsNr]) ?: '{}').'&page=1&start=0&limit=100') {
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
     * @param array<string, string|int|true> $data
     */
    public function fakeMember(array $data): self
    {
        return $this->fakeMembers([$data]);
    }

    public function singleMemberUrl(int $gruppierungId, int $memberId): string
    {
        return "https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/{$gruppierungId}/{$memberId}";
    }

    /**
     * @param array<int, array<string, mixed>> $data
     */
    public function fakeMembers(array $data): self
    {
        Http::fake(function($request) use ($data) {
            foreach ($data as $member) {
                if ($request->url() === $this->singleMemberUrl($member['gruppierungId'], $member['id']) && $request->method() === 'GET') {
                    $content = [
                        'success' => true,
                        'data' => $member,
                    ];

                    return Http::response(json_encode($content) ?: '{}', 200);
                }

                if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/{$member['id']}/flist") {
                    $content = [
                        'success' => true,
                        'data' => array_map(function($membership) {
                            return (object) [
                                'entries_aktivVon' => $membership['aktivVon'],
                                'entries_aktivBis' => $membership['aktivBis'],
                                'entries_gruppierung' => $membership['gruppierung'],
                                'id' => $membership['id'],
                                'entries_taetigkeit' => $membership['taetigkeit'],
                                'entries_untergliederung' => $membership['untergliederung'],
                            ];
                        }, $member['memberships'] ?? [])
                    ];

                    return Http::response(json_encode($content) ?: '{}', 200);
                }
            }

            foreach (collect($data)->chunk(100) as $i => $chunk) {
                if ($request->url() === 'https://nami.dpsg.de/ica/rest/nami/search-multi/result-list?searchedValues='.rawurlencode('{}').'&page='.($i+1).'&start='.($i*100).'&limit=100') {
                    return Http::response(json_encode([
                        'success' => true,
                        'totalEntries' => count($data),
                        'data' => collect($chunk)->map(fn ($member) => [
                            'entries_id' => $member['id'],
                            'entries_gruppierungId' => $member['gruppierungId'],
                        ])->toArray(),
                    ]) ?: '{}', 200);
                }
            }
        });

        foreach ($data as $member) {
            $courseFake = app(CourseFake::class)
                ->fetches($member['id'], collect(data_get($member, 'courses', []))->pluck('id')->toArray());
            foreach (data_get($member, 'courses', []) as $course) {
                $courseFake->fetchesSingle($member['id'], $course);
            }
        }

        return $this;
    }

    public function fakeSingleMembership(int $memberId, int $membershipId, array $data) {
        Http::fake(function($request) use ($data, $memberId, $membershipId) {
            if ($request->url() === "https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/{$memberId}/{$membershipId}") {
                $content = [
                    'success' => true,
                    'data' => $data,
                    'responseType' => 'OK',
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
    public function fakeCourses(array $data): self
    {
        Http::fake(function($request) use ($data) {
            if ($request->url() === 'https://nami.dpsg.de/ica/rest/module/baustein') {
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
     * @param array<int, array<int, array{name: string, id: int}>> $matches
     */
    public function fakeSubactivities(array $matches): self
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

    /**
     * @param array<int, array{name: string, id: int}> $data
     */
    private function dataResponse(array $data): PromiseInterface
    {
        $content = [
            'success' => true,
            'data' => collect($data)->map(fn ($item) => ['descriptor' => $item['name'], 'id' => $item['id'], 'name' => ''])->toArray(),
            'responseType' => 'OK',
            'totalEntries' => count($data),
        ];

        return Http::response(json_encode($content) ?: '{}', 200);
    }

}
