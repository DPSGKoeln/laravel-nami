<?php

namespace Zoomyboy\LaravelNami;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use Zoomyboy\LaravelNami\Nullable;

class Region extends Model implements Nullable {

    protected $guarded = [];

    public static function getNullValue() {
        return 23;
    }

    public static function fromNami($item) {
        return new self([
            'id' => $item['id'],
            'name' => preg_replace('/\s*\(.*?\)/', '', $item['descriptor'])
        ]);
    }

    public function getIsNullAttribute() {
        return $this->id == self::getNullValue();
    }

}
