<?php

namespace Zoomyboy\LaravelNami;

use Illuminate\Support\LazyCollection;

class MemberCollection extends LazyCollection
{
    public static function fromOverview($items)
    {
        $self = new self($items);

        return $self->map(function ($item) {
            return Member::fromOverview($item);
        });
    }
}
