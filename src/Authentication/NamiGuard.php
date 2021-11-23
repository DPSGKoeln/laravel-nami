<?php

namespace Zoomyboy\LaravelNami\Authentication;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Auth\SessionGuard;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Session\Store as SessionStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Zoomyboy\LaravelNami\LoginException;
use Zoomyboy\LaravelNami\Nami;
use Zoomyboy\LaravelNami\NamiUser;

class NamiGuard {

    use GuardHelpers;

    protected CacheRepository $cache;

    /** @var <int, callback> $loginCallbacks */
    public static array $loginCallbacks = [];

    /**
     * The currently authenticated user.
     *
     * @var ?NamiUser
     */
    protected $user;

    protected SessionStore $session;

    public function __construct(SessionStore $session, CacheRepository $cache) {
        $this->session = $session;
        $this->cache = $cache;
    }

    /**
     * Set the current user.
     *
     * @param  NamiUser|null $user
     * @return void
     */
    public function setUser(?NamiUser $user): void
    {
        $this->user = $user;
    }

    public function user(): ?NamiUser
    {
        if (! is_null($this->user)) {
            return $this->user;
        }

        $cache = $this->resolveCache();

        if (!$cache) {
            return null;
        }

        return NamiUser::fromPayload($cache);
    }

    /**
     * @param array<string, string> $credentials
     * @param bool $remember
     */
    public function attempt(array $credentials = [], bool $remember = false): bool
    {
        $beforeResult = static::runBeforeLogin($credentials, $remember);

        if (!is_null($beforeResult)) {
            return $beforeResult;
        }

        try {
            return $this->login($credentials);
        } catch (LoginException $e) {
            return false;
        }
    }

    /**
     * @param array<string, string> $credentials
     */
    public function login(array $credentials): bool
    {
        $api = Nami::login($credentials['mglnr'], $credentials['password']);
        $user = $api->findNr((int) $credentials['mglnr']);

        $payload = [
            'credentials' => $credentials,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'group_id' => $user->group_id,
        ];

        $this->setUser(NamiUser::fromPayload($payload));
        $key = $this->newCacheKey();
        Cache::forever("namiauth-{$key}", $payload);
        $this->updateSession($key);

        return true;
    }

    /**
     * @param array<string, string> $credentials
     * @param bool $remember
     */
    protected function runBeforeLogin(array $credentials, bool $remember): ?bool
    {
        foreach (static::$loginCallbacks as $callback) {
            $result = $callback($credentials, $remember);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    protected function updateSession(string $data): void
    {
        $this->session->put($this->getName(), $data);
        $this->session->migrate(true);
    }

    /**
     * @param callable $callback
     */
    public static function beforeLogin(callable $callback): void
    {
        static::$loginCallbacks[] = $callback;
    }

    public function getName(): string
    {
        return 'auth_key';
    }

    public function logout(): void
    {
        $this->session->forget($this->getName());
        $this->setUser(null);
    }

    /**
     * @return array<string, string>
     */
    private function resolveCache(): ?array
    {
        return $this->cache->get('namiauth-'.$this->session->get($this->getName()));
    }

    private function newCacheKey(): string
    {
        return Str::random(16);
    }

}
