<?php

namespace Zoomyboy\LaravelNami\Data;

use Spatie\DataTransferObject\Attributes\MapFrom;
use Spatie\DataTransferObject\DataTransferObject;

abstract class EnumData extends DataTransferObject {

    public int $id;

    #[MapFrom('descriptor')]
    public string $name;

}
