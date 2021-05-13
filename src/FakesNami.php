<?php

namespace Zoomyboy\LaravelNami;

use PHPUnit\Framework\Assert;

trait FakesNami {

    public function fakeNami() {
        Nami::fake();
    }

    public function fakeNamiMembers($members) {
        foreach ($members as $member) {
            Nami::addMember($member);
        }
    }

    public function assertMemberExists($groupId, $data) {
        Assert::assertNotNull($existing = Nami::member($groupId, $data['id']));

        foreach ($data as $key => $value) {
            Assert::assertEquals($value, $existing[$key]);
        }
    }

}
