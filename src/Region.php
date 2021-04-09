<?php

namespace Zoomyboy\LaravelNami;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;

class Region extends Model {

    protected $guarded = [];

    public static function fromNami($item) {
        return new self([
            'id' => $item['id'],
            'name' => preg_replace('/\s*\(.*?\)/', '', $item['descriptor'])
        ]);
    }

}
