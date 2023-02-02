<?php

namespace Zoomyboy\LaravelNami\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;

class Course extends Data
{
    public function __construct(
        public int $id,

        #[MapInputName('bausteinId')]
        public string $courseId,

        #[MapInputName('veranstalter')]
        public string $organizer,

        #[MapInputName('vstgName')]
        public string $eventName,

        #[MapInputName('vstgTag')]
        public string $completedAt,
    ) {
    }
}
