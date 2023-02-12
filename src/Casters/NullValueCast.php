<?php

namespace Zoomyboy\LaravelNami\Casters;

use DateTimeInterface;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Casts\Uncastable;
use Spatie\LaravelData\Support\DataProperty;

class NullValueCast implements Cast
{
    public function __construct(
        private int $id
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function cast(DataProperty $property, mixed $value, array $context): DateTimeInterface|Uncastable|null|int
    {
        if ($this->id === $value) {
            return null;
        }

        return $value;
    }
}
