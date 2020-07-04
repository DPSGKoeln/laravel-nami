<?php

namespace Zoomyboy\LaravelNami;

use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Illuminate\Contracts\Support\Arrayable;

class Group implements Arrayable {

    public $name;
    public $id;

    public static function fromResponse($response, $parent) {
        $group = new self();
        $group->name = $response['descriptor'];
        $group->id = $response['id'];
        $group->parent_id = $parent;

        return $group;
    }

    public function toArray() {
        return [ 'id' => $this->id, 'name' => $this->name, 'parent_id' => $this->parent_id ];
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

    public function activities(): Collection {
        return Nami::activities($this->id);
    }

}
