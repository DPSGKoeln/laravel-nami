<?php

namespace Zoomyboy\LaravelNami;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;

class Gender extends Model implements Nullable {

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

    public function getNameAttribute() {
        return ucfirst($this->attributes['name']);
    }

    public function getIsNullAttribute() {
        return $this->attributes['name'] == 'keine Angabe';
    }

}
