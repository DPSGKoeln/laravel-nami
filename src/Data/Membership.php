<?php

namespace Zoomyboy\LaravelNami\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
use Zoomyboy\LaravelNami\Casters\CarbonCast;

class Membership extends Data
{
    public function __construct(
        public ?int $id,

        #[MapInputName('gruppierungId')]
        public int $groupId,

        #[MapInputName('aktivVon')]
        #[WithCast(CarbonCast::class, format: 'Y-m-d H:i:s')]
        public Carbon $startsAt,

        #[MapInputName('aktivBis')]
        #[WithCast(CarbonCast::class, format: 'Y-m-d H:i:s')]
        public ?Carbon $endsAt,

        #[MapInputName('taetigkeitId')]
        public int $activityId,

        #[MapInputName('untergliederungId')]
        public ?int $subactivityId,

        #[MapInputName('gruppierung')]
        public ?string $group,
    ) {
    }

    /**
     * @return array<string, string|int|null>
     */
    public function toNami(): array
    {
        return [
            'gruppierungId' => $this->groupId,
            'id' => $this->id,
            'aktivVon' => $this->startsAt->format('Y-m-d').'T00:00:00',
            'aktivBis' => $this->endsAt ? $this->endsAt->format('Y-m-d').'T00:00:00' : null,
            'taetigkeitId' => $this->activityId,
            'untergliederungId' => $this->subactivityId,
        ];
    }

    public function group(): Group {
        return Group::from(['name' => $this->group ?: '', 'id' => $this->groupId]);
    }

}
