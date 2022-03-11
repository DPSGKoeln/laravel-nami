<?php

namespace Zoomyboy\LaravelNami\Data;

use Carbon\Carbon;
use Spatie\DataTransferObject\Attributes\CastWith;
use Spatie\DataTransferObject\Attributes\MapFrom;
use Spatie\DataTransferObject\DataTransferObject;
use Zoomyboy\LaravelNami\Casters\CarbonCaster;
use Zoomyboy\LaravelNami\Casters\NullableCarbonCaster;
use Zoomyboy\LaravelNami\Casters\NullableString;

class MembershipEntry extends DataTransferObject
{
    public ?int $id;

    #[MapFrom('entries_gruppierung')]
    public string $group;

    #[MapFrom('entries_aktivVon')]
    #[CastWith(CarbonCaster::class)]
    public Carbon $startsAt;

    #[MapFrom('entries_aktivBis')]
    #[CastWith(NullableCarbonCaster::class)]
    public ?Carbon $endsAt;

    #[MapFrom('entries_taetigkeit')]
    public string $activity;

    #[MapFrom('entries_untergliederung')]
    #[CastWith(NullableString::class)]
    public ?string $subactivity;
}
