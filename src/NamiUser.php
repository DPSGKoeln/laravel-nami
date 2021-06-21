<?php

namespace Zoomyboy\LaravelNami;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Cache;

class NamiUser implements Authenticatable {

    public $mglnr;
    public $password;

    public function __construct($attributes) {
        $this->mglnr = $attributes['mglnr'];
        $this->password = $attributes['password'];
    }

    public static function fromPayload($payload) {
        $user = new static([
            'mglnr' => data_get($payload, 'credentials.mglnr'),
            'password' => data_get($payload, 'credentials.password'),
        ]);

        return $user;
    }

    public function api() {
        return Nami::login($this->mglnr, $this->password);
    }

    public function getNamiGroupId() {
        return $this->api()->findNr($this->mglnr)->group_id;
    }

    public function getAuthIdentifierName() {
        return 'mglnr';
    }

    public function getMglnr() {
        return $this->mglnr;
    }

    public function getFirstname() {
        return Cache::remember('member-'.$this->mglnr.'-firstname', 3600, function() {
            return $this->api()->findNr($this->mglnr)->firstname;
        });
    }

    public function getLastname() {
        return Cache::remember('member-'.$this->mglnr.'-lastname', 3600, function() {
            return $this->api()->findNr($this->mglnr)->lastname;
        });
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
