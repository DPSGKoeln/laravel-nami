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

}
