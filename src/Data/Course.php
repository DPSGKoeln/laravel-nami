<?php

namespace Zoomyboy\LaravelNami\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
use Zoomyboy\LaravelNami\Casters\CarbonCast;
use Zoomyboy\LaravelNami\Tests\Factories\CourseFactory;

class Course extends Data
{
    public function __construct(
        public int $id,

        #[MapInputName('bausteinId')]
        public int $courseId,

        #[MapInputName('veranstalter')]
        public string $organizer,

        #[MapInputName('vstgName')]
        public string $eventName,

        #[MapInputName('vstgTag')]
        #[WithCast(CarbonCast::class, format: 'Y-m-d H:i:s')]
        public Carbon $completedAt,
    ) {
    }

    public static function factory(): CourseFactory
    {
        return CourseFactory::new();
    }
}
