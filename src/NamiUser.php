<?php

namespace Zoomyboy\LaravelNami;

use Illuminate\Contracts\Auth\Authenticatable;

class NamiUser implements Authenticatable {

    public $mglnr;

    public static function fromCredentials(array $credentials): ?self {
        $user = new static();
        $user->mglnr = $credentials['mglnr'];

        return $user;
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
