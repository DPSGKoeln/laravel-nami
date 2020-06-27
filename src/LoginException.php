<?php

namespace Zoomyboy\LaravelNami;

use Illuminate\Support\Str;

class LoginException extends \Exception {

    const TOO_MANY_FAILED_LOGINS = 1;
    const WRONG_CREDENTIALS = 2;

    public $response;
    public $reason = null;

    public function setResponse($response) {
        if (Str::startsWith($response['statusMessage'], 'Die höchste Anzahl von Login-Versuchen wurde erreicht')) {
            $this->setReason(self::TOO_MANY_FAILED_LOGINS);
        }

        if (Str::startsWith($response['statusMessage'], 'Benutzer nicht gefunden oder Passwort falsch')) {
            $this->setReason(self::WRONG_CREDENTIALS);
        }

        $this->response = $response;
    }

    public function setReason($reason) {
        $this->reason = $reason;
    }
}
