<?php

namespace Zoomyboy\LaravelNami;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;

class Country extends Model {

    protected $guarded = [];

    public static function fromNami($item) {
        return new self([
            'id' => $item['id'],
            'name' => $item['descriptor']
        ]);
    }

}
