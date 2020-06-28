<?php

namespace Zoomyboy\LaravelNami;

use Illuminate\Support\Collection;

class MemberCollection extends Collection {

    public static function fromOverview($items) {
        $self = new self($items);

        return $self->map(function($item) {
            return Member::fromOverview($item);
        });
    }

}
