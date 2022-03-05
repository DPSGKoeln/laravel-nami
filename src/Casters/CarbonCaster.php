<?php

namespace Zoomyboy\LaravelNami\Casters;

use Carbon\Carbon;
use Spatie\DataTransferObject\Caster;

class CarbonCaster implements Caster {

    public function cast(mixed $value): Carbon
    {
        return Carbon::parse($value);
    }


}
