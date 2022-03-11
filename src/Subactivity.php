<?php

namespace Zoomyboy\LaravelNami;

use Illuminate\Database\Eloquent\Model;

class Subactivity extends Model
{
    protected $guarded = [];

    public static function fromNami($item)
    {
        $item = collect($item)
            ->only(['descriptor', 'id'])
            ->mapWithKeys(function ($item, $key) {
                if ('id' == $key) {
                    return ['id' => $item];
                }

                return ['name' => $item];
            })->toArray();

        return new self($item);
    }

    public function getNameAttribute()
    {
        return ucfirst($this->attributes['name']);
    }

    public function getIsNullAttribute()
    {
        return $this->attributes['id'] == self::getNullValue();
    }
}
