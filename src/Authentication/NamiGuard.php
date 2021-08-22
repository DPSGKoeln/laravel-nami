<?php

namespace Zoomyboy\LaravelNami\Authentication;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\NamiUser;

class NamiGuard {

    use GuardHelpers;

    protected $cache;
    protected $user;
    protected $session;

    public function __construct($session, $cache) {
        $this->session = $session;
        $this->cache = $cache;
    }

    /**
     * Set the current user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return void
     */
    public function setUser(NamiUser $user) {
        $this->user = $user;
    }

    public function user()
    {
        if (! is_null($this->user)) {
            return $this->user;
        }

        $cache = $this->resolveCache();

        if (!$cache) {
            return;
        }

        return NamiUser::fromPayload($cache);
    }

    public function attempt(array $credentials = [], $remember = false) {
        $api = Nami::login($credentials['mglnr'], $credentials['password']);

        $payload = [
            'credentials' => $credentials
        ];

        $this->setUser(NamiUser::fromPayload($payload));
        $key = $this->newCacheKey();
        Cache::forever("namiauth-{$key}", $payload);
        $this->updateSession($key);

        return true;
    }

    protected function updateSession($data)
    {
        $this->session->put($this->getName(), $data);
        $this->session->migrate(true);
    }

    public function getName() {
        return 'auth_key';
    }

    private function resolveCache() {
        return $this->cache->get('namiauth-'.$this->session->get($this->getName()));
    }

    private function newCacheKey() {
        return Str::random(16);
    }

}
