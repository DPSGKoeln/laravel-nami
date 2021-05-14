<?php

namespace Zoomyboy\LaravelNami;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;

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
    ];

    protected $casts = [];

    protected $nullable = ['further_address', 'other_country', 'nickname', 'main_phone', 'mobile_phone', 'work_phone', 'fax', 'email', 'email_parents'];

    protected $guarded = [];

    public static function fromNami($item) {
        $item = collect($item)
            ->only(array_keys(static::$overviewAttributes))
            ->mapWithKeys(function($item, $key) {
                return [ data_get(static::$overviewAttributes, $key, $key) => $item ];
            })
            ->toArray();
        return (new self($item));
    }

    public static function fromAttributes($attributes) {
        return new self($attributes);
    }

    public function toNami() {
        return [
            'vorname' => $this->firstname,
            'nachname' => $this->lastname,
            'spitzname' => $this->nickname ?: '',
            'geschlechtId' => $this->gender_id ?: Gender::getNullValue(),
        ];
    }

    public function __construct($member) {
        parent::__construct($member);
    }

    public function getBirthdayAttribute() {
        return Carbon::parse($this->attributes['birthday'])->format('Y-m-d');
    }

    public function getJoinedAtAttribute() {
        $date = $this->attributes['joined_at'];

        return empty($date)
            ? null
            : Carbon::parse($date)->format('Y-m-d');
    }

    public function getGenderIdAttribute() {
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

    public function memberships() {
        $memberships = Nami::membershipsOf($this->id);

        return LazyCollection::make(function() use ($memberships) {
            foreach ($memberships as $membership) {
                yield $this->membership($membership['id']);
            }
        });
    }

    public function membership($id): Membership {
        return Membership::fromNami(Nami::membership($this->id, $id));
    }

}
