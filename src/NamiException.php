<?php

namespace Zoomyboy\LaravelNami;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class NamiException extends \Exception {

    private $data;
    public $response;
    public $request;

    public function setData($data) {
        $this->data = $data;
    }

    public function getData() {
        return $this->data;
    }

    public function request($request) {
        $this->request = $request;

        return $this;
    }

    public function response($response) {
        $this->response = $response;

        return $this;
    }

    public function report(): void
    {
        \Log::error($this->getMessage(), [
            'request' => $this->request,
            'response' => $this->response
        ]);

        throw ValidationException::withMessages(['id' => 'Unbekannter Fehler']);
    }
}
