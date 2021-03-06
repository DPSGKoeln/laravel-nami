<?php

namespace Zoomyboy\LaravelNami;

use Cache;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class NamiUserProvider implements UserProvider {

    private $model;

    public function __construct($model) {
        $this->model = $model;
    }

    public function retrieveById($identifier) {
        return $this->model::fromId($identifier);
    }

    public function retrieveByToken($identifier, $token) {

    }

    public function updateRememberToken(Authenticatable $user, $token) {

    }

    public function retrieveByCredentials(array $credentials) {
        return $this->model::fromCredentials($credentials);
    }
    
    public function validateCredentials(Authenticatable $user, array $credentials) {
        try {
            $api = $user->attemptNamiLogin($credentials['password']);

            $data = collect($api->allMembers()->data)->first(function($member) use ($credentials) {
                return $member->entries_mitgliedsNummer == $credentials['mglnr'];
            });
                
            Cache::forever('member.'.$credentials['mglnr'], [
                'data' => $api->getMember($data->id)->data,
                'credentials' => $credentials
            ]);

            return true;
        } catch (NamiException $e) {
            return false;
        }
    }
}
