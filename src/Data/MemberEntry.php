<?php

namespace Zoomyboy\LaravelNami\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Data;
use Zoomyboy\LaravelNami\Casters\StringCast;
use Zoomyboy\LaravelNami\Tests\Factories\MemberEntryRequestFactory;

class MemberEntry extends Data
{
    public function __construct(
        #[MapInputName('entries_vorname')]
        #[WithCast(StringCast::class)]
        public ?string $firstname,

        #[MapInputName('entries_nachname')]
        #[WithCast(StringCast::class)]
        public ?string $lastname,

        public int $id,

        #[MapInputName('entries_gruppierungId')]
        public int $groupId,
    ) {
    }

    public static function factory(): MemberEntryRequestFactory
    {
        return MemberEntryRequestFactory::new();
    }
}
