<?php

namespace Zoomyboy\LaravelNami\Casters;

use Carbon\Carbon;
use Spatie\DataTransferObject\Caster;

class NullableString implements Caster {

    public function cast(mixed $value): ?string
    {
        return $value ?: null;
    }


}
