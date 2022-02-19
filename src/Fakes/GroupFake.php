<?php

namespace Zoomyboy\LaravelNami\Fakes;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GroupFake extends Fake {

    public function get(array $data): void
    {
        $this->fakeResponse(null, $data);
    }

    private function fakeResponse(?int $parentId = null, array $data): void
    {
        $url = 'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/'.($parentId ?: 'root');
        Http::fake(function($request) use ($data, $url) {
            if ($request->url() === $url) {
                return Http::response(json_encode([
                    'success' => true,
                    'totalEntries' => count($this->mapToResponse($data)),
                    'data' => $this->mapToResponse($data),
                ]) ?: '{}', 200);
            }
        });

        foreach ($data as $id => $group) {
            $this->fakeResponse($id, data_get($group, 'children', []));
        }
    }

    public function assertRootFetched(): void
    {
        Http::assertSent(fn ($request) => $request->url() === 'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root'
            && $request->method() === 'GET'
        );
    }

    public function assertFetched(int $id): void
    {
        Http::assertSent(fn ($request) => $request->url() === 'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/'.$id
            && $request->method() === 'GET'
        );
    }

    private function mapToResponse(array $data): array
    {
        return collect($data)->map(fn ($group, $id) => [
            'id' => $id,
            'descriptor' => $group['name'],
        ])->values()->toArray();
    }

}
