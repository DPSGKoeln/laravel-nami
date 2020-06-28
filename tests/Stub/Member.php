<?php

namespace Zoomyboy\LaravelNami\Tests\Stub;

use Illuminate\Database\Eloquent\Model;

class Member extends Model {
    
    public $fillable = [ 'firstname', 'nami_id' ];

    public static function findByNamiId($id) {
        return self::where('nami_id', $id)->first();
    }
}
