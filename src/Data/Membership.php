<?php

namespace Zoomyboy\LaravelNami\Data;

use Carbon\Carbon;
use Spatie\DataTransferObject\Attributes\CastWith;
use Spatie\DataTransferObject\Attributes\MapFrom;
use Spatie\DataTransferObject\DataTransferObject;
use Zoomyboy\LaravelNami\Casters\CarbonCaster;
use Zoomyboy\LaravelNami\Casters\NullableCarbonCaster;

class Membership extends DataTransferObject {

    public int $id;

    #[MapFrom('gruppierungId')]
    public int $groupId;

    #[MapFrom('aktivVon')]
    #[CastWith(CarbonCaster::class)]
    public Carbon $startsAt;

    #[MapFrom('aktivBis')]
    #[CastWith(NullableCarbonCaster::class)]
    public ?Carbon $endsAt;

    #[MapFrom('taetigkeitId')]
    public int $activityId;

    #[MapFrom('untergliederungId')]
    public int $subactivityId;

}

