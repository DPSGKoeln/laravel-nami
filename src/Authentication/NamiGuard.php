<?php

namespace Zoomyboy\LaravelNami\Authentication;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\Authenticatable;
use Zoomyboy\LaravelNami\Nami;
use Illuminate\Support\Facades\Cache;
use Zoomyboy\LaravelNami\NamiUser;

class NamiGuard implements Guard {

    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check() {

    }

    /**
     * Determine if the current user is a guest.
     *
     * @return bool
     */
    public function guest() {

    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user() {

    }

    /**
     * Get the ID for the currently authenticated user.
     *
     * @return int|string|null
     */
    public function id() {

    }

    /**
     * Validate a user's credentials.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = []) {

    }

    /**
     * Set the current user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    public function setUser(Authenticatable $user) {
        
    }

    public function attempt($credentials) {
        $api = Nami::login($credentials['mglnr'], $credentials['password']);

        Cache::forever('namicookie-'.$credentials['mglnr'], [
            'user' => NamiUser::fromCredentials($credentials),
            'cookie' => $api->cookie->toArray(),
            'credentials' => $credentials
        ]);

        return true;
    }

}
