<?php

namespace Zoomyboy\LaravelNami\Fakes;

use Illuminate\Support\Facades\Http;

class GroupFake extends Fake
{
    public function fetches(?int $parent = null, array $data): self
    {
        $this->fakeResponse($parent, $data);

        return $this;
    }

    public function failsToFetch(int $parentId = null, ?string $error = 'wrong message'): void
    {
        $url = 'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/'.($parentId ?: 'root');
        Http::fake(function ($request) use ($url, $error) {
            if ($request->url() === $url) {
                return $this->errorResponse($error);
            }
        });
    }

    public function failsToFetchWithoutJson(int $parentId = null, ?string $error = 'wrong message'): void
    {
        $url = 'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/'.($parentId ?: 'root');
        Http::fake(function ($request) use ($url) {
            if ($request->url() === $url) {
                return $this->htmlResponse();
            }
        });
    }

    private function fakeResponse(?int $parentId = null, array $data): void
    {
        $url = 'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/'.($parentId ?: 'root');
        Http::fake(function ($request) use ($data, $url) {
            if ($request->url() === $url) {
                return Http::response(json_encode([
                    'success' => true,
                    'totalEntries' => count($this->mapToResponse($data)),
                    'data' => $this->mapToResponse($data),
                ]) ?: '{}', 200);
            }
        });
    }

    public function assertRootFetched(): void
    {
        Http::assertSent(fn ($request) => 'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/root' === $request->url()
            && 'GET' === $request->method()
        );
    }

    public function assertFetched(int $id): void
    {
        Http::assertSent(fn ($request) => $request->url() === 'https://nami.dpsg.de/ica/rest/nami/gruppierungen/filtered-for-navigation/gruppierung/node/'.$id
            && 'GET' === $request->method()
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
