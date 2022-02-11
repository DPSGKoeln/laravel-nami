<?php

namespace Zoomyboy\LaravelNami\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Zoomyboy\LaravelNami\Group;
use Zoomyboy\LaravelNami\LoginException;
use Zoomyboy\LaravelNami\Member;
use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\NamiServiceProvider;
use Zoomyboy\LaravelNami\Tests\TestCase;

class PushMemberTest extends TestCase
{
    public array $attributes = [
        [
            'firstname' => 'Max',
            'lastname' => 'Nach1',
            'group_id' => 103,
            'nickname' => 'spitz1',
            'gender_id' => 17,
            'joined_at' => '2021-02-02T00:00:00',
            'birthday' => '2021-02-02',
            'id' => 16,
        ], [
            'firstname' => 'Jane',
            'lastname' => 'Nach2',
            'nickname' => null,
            'group_id' => 103,
            'gender_id' => null,
            'joined_at' => '2021-02-02T00:00:00',
            'birthday' => '2021-02-02',
            'id' => 17,
        ]
    ];

    public function dataProvider(): array {
        return [
            'firstname' => [['firstname' => 'Max'], ['vorname' => 'Max']],
            'lastname' => [['lastname' => 'Nach1'], ['nachname' => 'Nach1']],
            'nickname' => [['nickname' => 'nick'], ['spitzname' => 'nick']],
            'nicknameEmpty' => [['nickname' => null], ['spitzname' => '']],
            'gender_id' => [['gender_id' => 17], ['geschlechtId' => 17]],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function test_push_a_single_member(array $overwrites, array $check): void {
        Http::fake(array_merge([
            'https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/103/16' => Http::response('{"success": true, "data": {"id": 16}}', 200),
        ]));

        $res = Nami::putMember(array_merge($this->attributes[0], $overwrites));
        $this->assertEquals(16, $res['id']);

        Http::assertSent(function($request) use ($check) {
            if ($request->url() != 'https://nami.dpsg.de/ica/rest/nami/mitglied/filtered-for-navigation/gruppierung/gruppierung/103/16' || $request->method() !== 'PUT') {
                return false;
            }

            foreach ($check as $key => $value) {
                if ($request[$key] !== $value) {
                    return false;
                }
            }

            return $request->method() === 'PUT';
        });

        Http::assertSentCount(2);
    }

}
