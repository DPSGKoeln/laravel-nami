<?php

namespace Zoomyboy\LaravelNami\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

class Member extends Data
{
    public function __construct(
        #[MapInputName('vorname')]
        public string $firstname,
        #[MapInputName('nachname')]
        public string $lastname,
        #[MapInputName('spitzname')]
        public string $nickname,
    ) {
    }
}
