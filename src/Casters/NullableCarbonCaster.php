<?php

namespace Zoomyboy\LaravelNami\Casters;

use Carbon\Carbon;
use Spatie\DataTransferObject\Caster;

class NullableCarbonCaster implements Caster {

    public function cast(mixed $value): ?Carbon
    {
        return $value
            ? Carbon::parse($value)
            : null;
    }


}
