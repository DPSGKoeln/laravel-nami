<?php

namespace Zoomyboy\LaravelNami\Casters;

use DateTimeInterface;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Casts\Uncastable;
use Spatie\LaravelData\Support\DataProperty;

class GenderCast implements Cast
{
    public function cast(DataProperty $property, mixed $value, array $context): DateTimeInterface|Uncastable|null|int
    {
        if (23 === $value) {
            return null;
        }

        return $value;
    }
}
