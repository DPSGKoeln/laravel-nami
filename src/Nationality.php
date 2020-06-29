<?php

namespace Zoomyboy\LaravelNami;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;

class Nationality extends Model {

    protected $guarded = [];

    public static function fromNami($item) {
        $item = collect($item)
            ->only(['descriptor', 'id'])
            ->mapWithKeys(function($item,$key) {
                if ($key == 'id') { return ['id' => $item]; }
                return ['name' => $item];
            })->toArray();

        return (new self($item));
    }

}
