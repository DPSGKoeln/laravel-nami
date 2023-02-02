<?php

namespace Zoomyboy\LaravelNami\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

abstract class EnumData extends Data
{
    public int $id;

    #[MapInputName('descriptor')]
    public string $name;
}
