<?php

namespace Zoomyboy\LaravelNami\Transformers;

use Spatie\LaravelData\Support\DataProperty;
use Spatie\LaravelData\Transformers\Transformer;

class CarbonTransformer implements Transformer
{
    public function __construct(
        protected array|string|null $format = null
    ) {
    }

    public function transform(DataProperty $property, mixed $value): mixed
    {
        if (is_null($value)) {
            return null;
        }

        return $value->format($this->format);
    }
}
