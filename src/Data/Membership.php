<?php

namespace Zoomyboy\LaravelNami\Data;

use Carbon\Carbon;
use Spatie\DataTransferObject\Attributes\CastWith;
use Spatie\DataTransferObject\Attributes\MapFrom;
use Spatie\DataTransferObject\DataTransferObject;
use Zoomyboy\LaravelNami\Casters\CarbonCaster;
use Zoomyboy\LaravelNami\Casters\NullableCarbonCaster;

class Membership extends DataTransferObject
{
    public ?int $id;

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

    #[MapFrom('gruppierung')]
    public ?string $group;

    public function toNami(): array
    {
        return [
            'gruppierungId' => $this->groupId,
            'id' => $this->id,
            'aktivVon' => $this->startsAt->format('Y-m-d').'T00:00:00',
            'aktivBis' => $this->endsAt ? $this->endsAt->toDateTimeString() : null,
            'taetigkeitId' => $this->activityId,
            'untergliederungId' => $this->subactivityId,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self([
            'gruppierungId' => data_get($data, 'groupId'),
            'id' => data_get($data, 'id'),
            'aktivVon' => data_get($data, 'startsAt'),
            'aktivBis' => data_get($data, 'endsAt'),
            'taetigkeitId' => data_get($data, 'activityId'),
            'untergliederungId' => data_get($data, 'subactivityId'),
        ]);
    }
}
