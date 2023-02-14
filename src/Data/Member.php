<?php

namespace Zoomyboy\LaravelNami\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Data;
use Zoomyboy\LaravelNami\Casters\CarbonCast;
use Zoomyboy\LaravelNami\Casters\NullValueCast;
use Zoomyboy\LaravelNami\Casters\StringCast;
use Zoomyboy\LaravelNami\Tests\Factories\MemberRequestFactory;
use Zoomyboy\LaravelNami\Transformers\CarbonTransformer;

class Member extends Data
{
    public function __construct(
        #[MapInputName('vorname')]
        #[WithCast(StringCast::class)]
        public ?string $firstname,

        #[MapInputName('nachname')]
        #[WithCast(StringCast::class)]
        public ?string $lastname,

        #[MapInputName('spitzname')]
        #[WithCast(StringCast::class)]
        public ?string $nickname,

        #[MapInputName('beitragsartId')]
        public ?int $feeId,

        #[MapInputName('eintrittsdatum')]
        #[WithCast(CarbonCast::class, format: 'Y-m-d H:i:s')]
        #[WithTransformer(CarbonTransformer::class, format: 'Y-m-d H:i:s')]
        public Carbon $joinedAt,

        #[MapInputName('geburtsDatum')]
        #[WithCast(CarbonCast::class, format: 'Y-m-d H:i:s')]
        #[WithTransformer(CarbonTransformer::class, format: 'Y-m-d H:i:s')]
        public Carbon $birthday,

        public ?string $email,

        #[MapInputName('geschlechtId')]
        #[WithCast(NullValueCast::class, id: 23)]
        public ?int $genderId,

        #[MapInputName('konfessionId')]
        public ?int $confessionId,

        #[MapInputName('landId')]
        public int $countryId,

        #[MapInputName('lastUpdated')]
        #[WithCast(CarbonCast::class, format: 'Y-m-d H:i:s')]
        #[WithTransformer(CarbonTransformer::class, format: 'Y-m-d H:i:s')]
        public ?Carbon $updatedAt,

        #[MapInputName('nameZusatz')]
        #[WithCast(StringCast::class)]
        public ?string $furtherAddress,

        #[MapInputName('emailVertretungsberechtigter')]
        #[WithCast(StringCast::class)]
        public ?string $emailParents,

        public ?int $id,

        #[MapInputName('gruppierungId')]
        public int $groupId,

        #[MapInputName('gruppierung')]
        public ?string $groupName,

        #[MapInputName('mitgliedsNummer')]
        public ?int $memberId,

        #[MapInputName('plz')]
        public ?string $zip,

        #[MapInputName('ort')]
        public ?string $location,

        #[WithCast(NullValueCast::class, id: 23)]
        public ?int $regionId,

        #[MapInputName('staatsangehoerigkeitId')]
        public int $nationalityId,

        #[MapInputName('strasse')]
        public ?string $address,

        #[MapInputName('telefax')]
        #[WithCast(StringCast::class)]
        public ?string $fax,

        #[MapInputName('telefon1')]
        #[WithCast(StringCast::class)]
        public ?string $mainPhone,

        #[MapInputName('telefon2')]
        #[WithCast(StringCast::class)]
        public ?string $mobilePhone,

        #[MapInputName('telefon3')]
        #[WithCast(StringCast::class)]
        public ?string $workPhone,

        #[MapInputName('staatsangehoerigkeitText')]
        #[WithCast(StringCast::class)]
        public ?string $otherCountry,

        public ?int $version,

        #[MapInputName('wiederverwendenFlag')]
        public bool $keepdata,

        #[MapInputName('zeitschriftenversand')]
        public bool $sendNewspaper,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toNami(): array
    {
        return [
            'spitzname' => $this->nickname ?: '',
            'vorname' => $this->firstname ?: '',
            'nachname' => $this->lastname ?: '',
            'geschlechtId' => $this->genderId ?: 23,
            'email' => $this->email,
            'beitragsartId' => $this->feeId,
            'geburtsDatum' => $this->birthday->format('Y-m-d 00:00:00'),
            'konfessionId' => $this->confessionId,
            'landId' => $this->countryId,
            'wiederverwendenFlag' => $this->keepdata,
            'regionId' => $this->regionId ?: 23,
            'staatsangehoerigkeitId' => $this->nationalityId,
            'emailVertretungsberechtigter' => $this->emailParents,
            'ort' => $this->location,
            'plz' => $this->zip,
            'strasse' => $this->address,
            'telefax' => $this->fax,
            'telefon1' => $this->mainPhone,
            'telefon2' => $this->mobilePhone,
            'telefon3' => $this->workPhone,
            'zeitschriftenversand' => $this->sendNewspaper,
            'staatsangehoerigkeitText' => $this->otherCountry,
            'nameZusatz' => $this->furtherAddress,
            'version' => $this->version,
            'eintrittsdatum' => $this->joinedAt->format('Y-m-d 00:00:00'),
        ];
    }

    public static function factory(): MemberRequestFactory
    {
        return MemberRequestFactory::new();
    }

    /**
     * @param array<string, mixed> $rawData
     */
    public static function isCorrupted(array $rawData): bool
    {
        if (!data_get($rawData, 'eintrittsdatum')) {
            return false;
        }

        if (!data_get($rawData, 'landId')) {
            return false;
        }

        return true;
    }
}
