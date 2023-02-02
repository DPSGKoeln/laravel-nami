<?php

namespace Zoomyboy\LaravelNami\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
use Zoomyboy\LaravelNami\Casters\StringCast;

class MemberEntry extends Data
{
    public function __construct(
        #[MapInputName('entries_vorname')]
        #[WithCast(StringCast::class)]
        public ?string $firstname,

        #[MapInputName('entries_nachname')]
        #[WithCast(StringCast::class)]
        public ?string $lastname,
    ) {
    }
}
