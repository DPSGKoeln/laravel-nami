<?php

namespace Zoomyboy\LaravelNami\Casters;

use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Support\DataProperty;

class StringCast implements Cast
{
    public function __construct()
    {
    }

    public function cast(DataProperty $property, mixed $value, array $context): ?string
    {
        if ($property->type->isNullable && !$value) {
            return null;
        }

        return $value;
    }
}
