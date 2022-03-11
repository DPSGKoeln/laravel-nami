<?php

namespace Zoomyboy\LaravelNami;

use Illuminate\Database\Eloquent\Model;

class Fee extends Model
{
    protected $guarded = [];

    public static function fromNami($item)
    {
        $name = preg_replace('/DPSG Bundesverband 000000 \((.*?) - VERBANDSBEITRAG\)/', '\\1', $item['descriptor']);

        return new self([
            'name' => $name,
            'id' => $item['id'],
        ]);
    }
}
