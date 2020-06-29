<?php

namespace Zoomyboy\LaravelNami;

interface Nullable {

    public function getIsNullAttribute();

    public static function getNullValue();

}
