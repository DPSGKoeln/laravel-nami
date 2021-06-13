<?php

namespace Zoomyboy\LaravelNami\Providers;

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

            $group = $api->group($credentials['groupid']);
            $data = $group->memberOverview()->first(function($member) use ($credentials) {
                return $member->mitgliedsnr == $credentials['mglnr'];
            });

            if (!$data) {
                return false;
            }
                
            Cache::forever('namicookie-'.$credentials['mglnr'], [
                'data' => $data->toArray(),
                'cookie' => $api->cookie->toArray(),
                'credentials' => $credentials
            ]);

            return true;
        } catch (NamiException $e) {
            return false;
        }
    }
}
