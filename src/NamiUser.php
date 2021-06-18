<?php

namespace Zoomyboy\LaravelNami;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class NamiUser extends Model implements Authenticatable {

    public $mglnr;
    public $password;

    public function __construct($payload) {
        $this->mglnr = data_get($payload, 'credentials.mglnr');
        $this->password = data_get($payload, 'credentials.password');
    }

    public function api() {
        return Nami::login($this->mglnr, $this->password);
    }

    public function getNamiGroupId() {
        return $this->groupid;
    }

    public function getAuthIdentifierName() {
        return 'mglnr';
    }

    public function getFirstnameAttribute() {
        return $this->api()->findNr($this->mglnr)->vorname;
    }

    public function getLastnameAttribute() {
        return $this->api()->findNr($this->mglnr)->nachname;
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
