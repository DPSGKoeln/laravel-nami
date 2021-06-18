<?php

namespace Zoomyboy\LaravelNami;

use Illuminate\Contracts\Auth\Authenticatable;

class NamiUser implements Authenticatable {

    public $mglnr;
    public $password;

    public function __construct($payload) {
        $this->mglnr = data_get($payload, 'credentials.mglnr');
        $this->password = data_get($payload, 'credentials.password');
        $this->cookie = data_get($payload, 'cookie');
    }

    public function getNamiApi() {
        return $this->attemptNamiLogin(cache('member.'.$this->mglnr)['credentials']['password']);
    }

    public function getNamiGroupId() {
        return $this->groupid;
    }

    public function getAuthIdentifierName() {
        return 'mglnr';
    }

    public function getAuthIdentifier() {
        return $this->{$this->getAuthIdentifierName()}.'-'.$this->groupid;
    }

    public function getAuthPassword() {
        return null;
    }

    public function getRememberToken() {
        return null;
    }

    public function setRememberToken($value) {}

    public function getRememberTokenName() {
        return null;
    }
}
