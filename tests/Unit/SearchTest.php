<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Zoomyboy\LaravelNami\Exceptions\NotSuccessfulException;
use Zoomyboy\LaravelNami\Fakes\SearchFake;
use Zoomyboy\LaravelNami\Tests\TestCase;

class SearchTest extends TestCase
{
    /**
     * @var array<int, array<string, string|int|null>>
     */
    public array $attributes = [
        [
            'firstname' => 'Max',
            'lastname' => 'Nach1',
            'group_id' => 103,
            'nickname' => 'spitz1',
            'gender_id' => 17,
            'id' => 16,
        ], [
            'firstname' => 'Jane',
            'lastname' => 'Nach2',
            'nickname' => null,
            'group_id' => 103,
            'gender_id' => null,
            'id' => 17,
        ],
    ];

    /**
     * @return array<string, array{0: string, 1: array<int, string>}>
     */
    public function dataProvider(): array
    {
        return [
            'firstname' => ['vorname', ['Max', 'Jane']],
        ];
    }

    public function testFindAMemberByMglnr(): void
    {
        Http::fake([
            $this->url(['mitgliedsNummber' => 150]) => Http::response($this->fakeJson('searchResponse.json'), 200),
        ]);

        $member = $this->login()->findNr(150);

        $this->assertEquals('Philipp', $member->firstname);
        $this->assertEquals(89418, $member->id);
        $this->assertEquals(100105, $member->groupId);
        Http::assertSent(function ($request) {
            return $request->url() == $this->url(['mitgliedsNummber' => 150])
                && 'GET' == $request->method();
        });

        Http::assertSentCount(1);
    }

    public function testItThrowsExceptionWhenSearchFails(): void
    {
        $this->withoutExceptionHandling()->expectException(NotSuccessfulException::class);
        app(SearchFake::class)->fetchFails($page = 1, $start = 0, 'unknown error');

        $this->login()->search([])->first();
    }

    /**
     * @param array<string, string|int|null> $payload
     */
    private function url(array $payload): string
    {
        $payload = rawurlencode(json_encode($payload));

        return "https://nami.dpsg.de/ica/rest/nami/search-multi/result-list?searchedValues={$payload}&page=1&start=0&limit=100";
    }
}
