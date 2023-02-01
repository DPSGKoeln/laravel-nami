<?php

namespace Zoomyboy\LaravelNami\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\DateTimeInterfaceCast;
use Spatie\LaravelData\Data;
use Zoomyboy\LaravelNami\Casters\CarbonCast;
use Zoomyboy\LaravelNami\Casters\StringCast;

class MembershipEntry extends Data
{
    public function __construct(
        public ?int $id,

        #[MapInputName('entries_gruppierung')]
        public string $group,

        #[MapInputName('entries_aktivVon')]
        #[WithCast(DateTimeInterfaceCast::class, format: 'Y-m-d H:i:s')]
        public Carbon $startsAt,

        #[MapInputName('entries_aktivBis')]
        #[WithCast(CarbonCast::class, format: 'Y-m-d H:i:s')]
        public ?Carbon $endsAt,

        #[MapInputName('entries_taetigkeit')]
        public ?string $activity,

        #[MapInputName('entries_untergliederung')]
        #[WithCast(StringCast::class)]
        public ?string $subactivity,
    ) {
    }
}
