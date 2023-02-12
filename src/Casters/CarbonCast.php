<?php

namespace Zoomyboy\LaravelNami\Casters;

use DateTimeInterface;
use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Casts\Uncastable;
use Spatie\LaravelData\Support\DataProperty;

class CarbonCast implements Cast
{
    public function __construct(
        protected ?string $format = null
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function cast(DataProperty $property, mixed $value, array $context): DateTimeInterface|Uncastable|null
    {
        if ($property->type->isNullable && !$value) {
            return null;
        }

        return (new DateTimeInterfaceCast($this->format, null, null, null))->cast($property, $value, $context);
    }
}
