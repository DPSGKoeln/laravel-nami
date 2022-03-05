<?php

namespace Zoomyboy\LaravelNami;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Zoomyboy\LaravelNami\Data\Membership;
use Zoomyboy\LaravelNami\Exceptions\RightException;

class Member extends Model {

    public $timestamps = false;

    public $geschlechtMaps = [
        'männlich' => 19,
        'weiblich' => 20,
        'keine Angabe' => 23
    ];

    protected static $overviewAttributes = [
        'vorname' => 'firstname',
        'nachname' => 'lastname',
        'spitzname' => 'nickname',
        'staatsangehoerigkeitText' => 'other_country',
        'staatangehoerigkeitText' => 'other_country',
        'strasse' => 'address',
        'nameZusatz' => 'further_address',
        'plz' => 'zip',
        'ort' => 'location',
        'id' => 'id',
        'telefon1' => 'main_phone',
        'telefon2' => 'mobile_phone',
        'telefon3' => 'work_phone',
        'telefax' => 'fax',
        'email' => 'email',
        'geschlecht' => 'geschlecht_text',
        'geschlechtId' => 'gender_id',
        'emailVertretungsberechtigter' => 'email_parents',
        'staatsangehoerigkeitId' => 'nationality_id',
        'konfessionId' => 'confession_id',
        'geburtsDatum' => 'birthday',
        'eintrittsdatum' => 'joined_at',
        'gruppierungId' => 'group_id',
        'lastUpdated' => 'updated_at',
        'mitgliedsNummer' => 'mitgliedsnr',
        'zeitschriftenversand' => 'send_newspaper',
        'regionId' => 'region_id',
        'landId' => 'country_id',
        'beitragsartId' => 'fee_id',
        'version' => 'version',
        'gruppierung' => 'group_name',
    ];

    protected $casts = [];

    protected $nullable = ['further_address', 'other_country', 'nickname', 'main_phone', 'mobile_phone', 'work_phone', 'fax', 'email', 'email_parents'];

    protected $guarded = [];

    public static function fromNami($item): self
    {
        $item = collect($item)
            ->only(array_keys(static::$overviewAttributes))
            ->mapWithKeys(function($item, $key) {
                return [ data_get(static::$overviewAttributes, $key, $key) => $item ];
            })
            ->toArray();
        return (new self($item));
    }

    public static function fromAttributes(array $attributes): self
    {
        return new self($attributes);
    }

    public function toNami(): array
    {
        return [
            'vorname' => $this->firstname,
            'nachname' => $this->lastname,
            'spitzname' => $this->nickname ?: '',
            'strasse' => $this->address,
            'plz' => $this->zip,
            'ort' => $this->location,
            'eintrittsdatum' => $this->joined_at.'T00:00:00',
            'version' => $this->version,
            'beitragsartId' => $this->fee_id,
            'regionId' => $this->region_id ?: Region::getNullValue(),
            'landId' => $this->country_id,
            'staatsangehoerigkeitId' => $this->nationality_id,
            'geburtsDatum' => $this->birthday,
            'geschlechtId' => $this->gender_id ?: Gender::getNullValue(),
            'gruppierungId' => $this->group_id,
            'id' => $this->id,
            'zeitschriftenversand' => $this->send_newspaper,
            'telefon1' => $this->main_phone ?: '',
            'telefon2' => $this->mobile_phone ?: '',
            'telefon3' => $this->work_phone ?: '',
            'telefax' => $this->fax ?: '',
            'email' => $this->email ?: '',
            'emailVertretungsberechtigter' => $this->email_parents ?: '',
            'geburtsDatum' => $this->birthday.' 00:00:00',
            'nameZusatz' => $this->further_address ?: '',
            'konfessionId' => $this->confession_id,
            'staatsangehoerigkeitText' => $this->other_country ?: '',
            'ersteTaetigkeitId' => $this->first_activity_id ?: null,
            'ersteUntergliederungId' => $this->first_subactivity_id ?: null,
        ];
    }

    public function getBirthdayAttribute(): ?string
    {
        return Carbon::parse($this->attributes['birthday'])->format('Y-m-d');
    }

    public function getJoinedAtAttribute(): ?string
    {
        $date = $this->attributes['joined_at'];

        return empty($date)
            ? null
            : Carbon::parse($date)->format('Y-m-d');
    }

    public function getGenderIdAttribute(): ?int
    {
        return $this->attributes['gender_id'] == Gender::getNullValue() ? null : $this->attributes['gender_id'];
    }

    public function setGeschlechtTextAttribute($v) {
        $this->attributes['gender_id'] = data_get($this->geschlechtMaps, $v, null);
    }

    public function setAttribute($key, $value) {
        if (in_array($key, $this->nullable) && $value === '') {
            return parent::setAttribute($key, null);
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * @return Collection<Membership>
     */
    public function memberships(): Collection
    {
        return Nami::membershipsOf($this->id);
    }

    public function putMembership(array $data): int
    {
        return Nami::putMembership($this->id, [
            'gruppierungId' => $data['group_id'],
            'taetigkeitId' => $data['activity_id'],
            'untergliederungId' => $data['subactivity_id'],
            'aktivVon' => $data['starts_at']->format('Y-m-d').'T00:00:00',
        ]);
    }

    public function deleteMembership(int $id): int
    {
        $membership = $this->membership($id);

        return Nami::putMembership($this->id, [
            'gruppierungId' => $membership->group_id,
            'aktivVon' => $membership->starts_at.'T00:00:00',
            'aktivBis' => now()->format('Y-m-d').'T00:00:00',
            'id' => $membership->id,
        ]);
    }

    public function membership($id): ?Membership {
        try {
            return Membership::fromNami(Nami::membership($this->id, $id));
        } catch (RightException $e) {
            return null;
        }
    }

}
