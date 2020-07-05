<?php

namespace Zoomyboy\LaravelNami;

use Log;

class Logger {
    public $errors = [ 404, 403, 401, 500 ];
    private $response;
    private $title;
    public $options;
    private $url;

    public static function http($url, $response, $title, $options) {
        $logger = new static($url, $response, $title, $options);
        return $logger->fromHttp();
    }

    public function __construct($url, $response, $title, $options) {
        $this->url = $url;
        $this->response = $response;
        $this->title = $title;
        $this->options = $options;
    }

    public function fromHttp() {
        if (is_null($level = $this->level())) { return $this; }
        Log::{$level}($this->title, array_merge([
            'url' => $this->url,
            'response' => $this->response->body(),
            'json' => $this->response->json()
        ], $this->options));
    }

    public function level() {
        return in_array($this->response->status(), $this->errors) || $this->response['success'] == false
            ? 'error'
            : null;
    }
}
