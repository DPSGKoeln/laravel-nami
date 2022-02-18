<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Zoomyboy\LaravelNami\Member;
use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\Tests\TestCase;

class PushMembershipsTest extends TestCase
{

    public function test_create_a_membership(): void
    {
        Carbon::setTestNow(Carbon::parse('2021-02-03 06:00:00'));
        Http::fake([
            'https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/16/flist' => Http::response($this->fakeJson('membership-overview.json'), 200),
            'https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/16' => Http::response($this->fakeJson('membership-create.json'), 200),
        ]);

        $member = new Member(['id' => 16]);
        $id = $member->putMembership([
            'created_at' => now(),
            'group_id' => 150,
            'activity_id' => 56,
            'subactivity_id' => 89,
            'starts_at' => now(),
        ]);
        $this->assertEquals(65, $id);

        Http::assertSentCount(1);

        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->url() === 'https://nami.dpsg.de/ica/rest/nami/zugeordnete-taetigkeiten/filtered-for-navigation/gruppierung-mitglied/mitglied/16'
            && $request['gruppierungId'] === 150
            && $request['taetigkeitId'] === 56
            && $request['untergliederungId'] === 89
            && $request['aktivVon'] === '2021-02-03T00:00:00'
        );
    }

}
