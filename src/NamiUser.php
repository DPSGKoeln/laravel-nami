<?php

namespace Zoomyboy\LaravelNami;

use Illuminate\Contracts\Auth\Authenticatable;

class NamiUser implements Authenticatable {

    private $mglnr;
    private $groupid;
    public $name = 'DDD';
    public $email = 'III';

    public static function fromCredentials(array $credentials): ?self {
        $user = new static();
        $user->mglnr = $credentials['mglnr'];
        $user->groupid = $credentials['groupid'];

        return $user;
    }

    public function getNamiApi() {
        return $this->attemptNamiLogin(cache('member.'.$this->mglnr)['credentials']['password']);
    }

    public function attemptNamiLogin($password) {
        return Nami::login($this->mglnr, $password, $this->groupid);
    }

    public function getNamiGroupId() {
        return $this->groupid;
    }

    public static function fromId($id) {
        list($mglnr, $groupid) = explode('-', $id);

        $user = new static();
        $user->mglnr = $mglnr;
        $user->groupid = $groupid;

        return $user;
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
