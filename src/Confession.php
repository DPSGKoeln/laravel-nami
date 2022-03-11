<?php

namespace Zoomyboy\LaravelNami;

use Illuminate\Database\Eloquent\Model;

class Confession extends Model
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
}
