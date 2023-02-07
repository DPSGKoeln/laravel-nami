<?php

namespace Zoomyboy\LaravelNami\Fakes;

use Illuminate\Support\Facades\Http;
use Zoomyboy\LaravelNami\Data\MemberEntry;

class SearchFake extends Fake
{
    public function fetchFails(int $page, int $start, ?string $error = 'wrong message'): void
    {
        Http::fake(function ($request) use ($error, $page, $start) {
            if ($request->url() === 'https://nami.dpsg.de/ica/rest/nami/search-multi/result-list?searchedValues='.rawurlencode('{}').'&page='.$page.'&start='.$start.'&limit=100') {
                return $this->errorResponse($error);
            }
        });
    }

    /**
     * @param array<int, MemberEntry> $data
     */
    public function fetches(int $page, int $start, array $data): void
    {
        Http::fake(function ($request) use ($page, $start, $data) {
            if ($request->url() === 'https://nami.dpsg.de/ica/rest/nami/search-multi/result-list?searchedValues='.rawurlencode('{}').'&page='.$page.'&start='.$start.'&limit=100') {
                return $this->collection(collect($data)->map(fn ($member) => [
                    'id' => $member->id,
                    'entries_gruppierungId' => $member->groupId,
                    'entries_nachname' => $member->lastname,
                    'entries_vorname' => $member->firstname,
                ]));
            }
        });
    }
}
