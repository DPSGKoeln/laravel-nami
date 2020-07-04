<?php

namespace Zoomyboy\LaravelNami;

use Illuminate\Contracts\Support\Arrayable;

class Group implements Arrayable {

    public $name;
    public $id;

    public static function fromResponse($response) {
        $group = new self();
        $group->name = $response['descriptor'];
        $group->id = $response['id'];

        return $group;
    }

    public function toArray() {
        return [ 'id' => $this->id, 'name' => $this->name ];
    }

    public function subgroups() {
        return Nami::subgroupsOf($this->id);
    }

    public function members(): MemberCollection {
        $members = Nami::membersOf($this->id);

        return MemberCollection::make(function() use ($members) {
            foreach ($members as $member) {
                yield $this->member($member['id']);
            }
        });
        return new MemberCollection(Nami::membersOf($this->id)->map(function($member) {
            return $this->member($member['id']);
        }));
    }

    public function member($id): Member {
        return Member::fromNami(Nami::member($this->id, $id));
    }

}
