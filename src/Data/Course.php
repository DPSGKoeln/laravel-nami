<?php

namespace Zoomyboy\LaravelNami\Data;

use Spatie\DataTransferObject\Attributes\MapFrom;
use Spatie\DataTransferObject\DataTransferObject;

class Course extends DataTransferObject
{
    public int $id;

    #[MapFrom('bausteinId')]
    public string $courseId;

    #[MapFrom('veranstalter')]
    public string $organizer;

    #[MapFrom('vstgName')]
    public string $eventName;

    #[MapFrom('vstgTag')]
    public string $completedAt;
}
