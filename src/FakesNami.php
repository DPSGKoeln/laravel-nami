<?php

namespace Zoomyboy\LaravelNami;

use PHPUnit\Framework\Assert;
use Zoomyboy\LaravelNami\Backend\Backend;

trait FakesNami {

    public function fakeNami() {
        Backend::fake();
    }

    public function fakeNamiMembers($members) {
        foreach ($members as $member) {
            Backend::addMember($member);
        }
    }

    public function assertMemberExists($groupId, $data) {
        Assert::assertNotNull($existing = Nami::member($groupId, $data['id']));

        foreach ($data as $key => $value) {
            Assert::assertEquals($value, $existing[$key]);
        }
    }

}
