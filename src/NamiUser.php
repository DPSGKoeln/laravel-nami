<?php

namespace Zoomyboy\LaravelNami;

use Cache;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class NamiUser {

    public $mglnr;
    public $password;
    public string $firstname;
    public string $lastname;
    public int $group_id;

    public function __construct($attributes) {
        $this->mglnr = $attributes['mglnr'];
        $this->password = $attributes['password'];
        $this->firstname = $attributes['firstname'];
        $this->lastname = $attributes['lastname'];
        $this->group_id = $attributes['group_id'];
    }

    public static function fromPayload($payload) {
        $user = new static([
            'mglnr' => data_get($payload, 'credentials.mglnr'),
            'password' => data_get($payload, 'credentials.password'),
            'firstname' => data_get($payload, 'firstname'),
            'lastname' => data_get($payload, 'lastname'),
            'group_id' => data_get($payload, 'group_id'),
        ]);

        return $user;
    }

    public function api() {
        return Nami::login($this->mglnr, $this->password);
    }

    public function getNamiGroupId() {
        return $this->group_id;
    }

    public function getMglnr() {
        return $this->mglnr;
    }

    public function getFirstname() {
        return $this->firstname;
    }

    public function getLastname() {
        return $this->lastname;
    }

}
