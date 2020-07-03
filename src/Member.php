<?php

namespace Zoomyboy\LaravelNami;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Member extends Model {

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
    ];

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

    public function __construct($member) {
        parent::__construct($member);
    }

    public function getBirthdayAttribute() {
        return Carbon::parse($this->attributes['birthday'])->format('Y-m-d');
    }

    public function getJoinedAtAttribute() {
        return Carbon::parse($this->attributes['joined_at'])->format('Y-m-d');
    }

    public function getGenderIdAttribute() {
        return $this->attributes['gender_id'] == Gender::getNullValue() ? null : $this->attributes['gender_id'];
    }

    public function setGeschlechtTextAttribute($v) {
        $this->attributes['gender_id'] = data_get($this->geschlechtMaps, $v, null);
    }

}
