<?php

namespace Zoomyboy\LaravelNami;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\LazyCollection;

class Membership extends Model {

    protected static $overviewAttributes = [
        'id' => 'id',
        'gruppierungId' => 'group_id',
        'taetigkeitId' => 'activity_id',
        'untergliederungId' => 'subactivity_id',
        'aktivVon' => 'starts_at',
        'aktivBis' => 'ends_at'
    ];

    protected $casts = [];

    protected $nullable = [];

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

    public function __construct($membership) {
        parent::__construct($membership);
    }

    public function setAttribute($key, $value) {
        if (in_array($key, $this->nullable) && $value === '') {
            return parent::setAttribute($key, null);
        }

        return parent::setAttribute($key, $value);
    }

    public function setStartsAtAttribute($date) {
        $this->attributes['starts_at'] = empty($date)
            ? null
            : Carbon::parse($date)->format('Y-m-d');
    }

    public function setEndsAtAttribute($date) {
        $this->attributes['ends_at'] = empty($date)
            ? null
            : Carbon::parse($date)->format('Y-m-d');
    }

}
