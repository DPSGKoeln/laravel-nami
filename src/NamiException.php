<?php

namespace Zoomyboy\LaravelNami;

use Illuminate\Support\Str;

class NamiException extends \Exception {

    private $data;

    public function setData($data) {
        $this->data = $data;
    }

    public function getData() {
        return $this->data;
    }
}
