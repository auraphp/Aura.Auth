<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Auth;

use Aura\Auth\Adapter;
use Aura\Auth\Service;
use Aura\Auth\Session;
use Aura\Session_Interface\SessionInterface;
use Aura\Session_Interface\SegmentInterface;
use Aura\Auth\Verifier;
use Aura\Auth\Adapter\AdapterInterface;
use Aura\Auth\Remember;
use Aura\Auth\Remember\RememberService;
use Aura\Auth\Remember\RememberStorageInterface;
use Aura\Auth\OAuth;
use Aura\Auth\OAuth\ProviderInterface;
use Aura\Auth\Throttle;
use Aura\Auth\Throttle\ThrottleStorageInterface;
use Aura\Auth\Throttle\ThrottleService;
use Aura\Auth\Throttle\RedisClientInterface;
use PDO;

/**
 *
 * Factory for Auth package objects.
 *
 * @package Aura.Auth
 *
 */

class AuthFactory
{
    /**
     *
     * A session manager.
     *
     * @var SessionInterface
     *
     */
    protected $session;

    /**
     *
     * A session segment.
     *
     * @var SegmentInterface
     *
     */
    protected $segment;

    /**
     *
     * A copy of the $_COOKIE array.
     *
     * @var array
     *
     */
    protected $cookie;

    /**
     *
     * Constructor.
     *
     * @param array $cookie A copy of $_COOKIES.
     *
     * @param SessionInterface $session A session manager.
     *
     * @param SegmentInterface $segment A session segment.
     *
     */
    public function __construct(
        array $cookie,
        ?SessionInterface $session = null,
        ?SegmentInterface $segment = null
    ) {
        $this->cookie = $cookie;
        $this->session = $session;
        if (! $this->session) {
            $this->session = new Session\Session($cookie);
        }

        $this->segment = $segment;
        if (! $this->segment) {
            $this->segment = new Session\Segment;
        }
    }

    /**
     *
     * Returns a new authentication tracker.
     *
     * @return Auth
     *
     */
    public function newInstance(): Auth
    {
        return new Auth($this->segment);
    }

    /**
     *
     * Returns a new login service instance.
     *
     * @param AdapterInterface $adapter The adapter to use with the service.
     *
     * @return Service\LoginService
     *
     */
    public function newLoginService(
        ?AdapterInterface $adapter = null,
        ?RememberService $remember_service = null
    ): Service\LoginService {
        return new Service\LoginService(
            $this->fixAdapter($adapter),
            $this->session,
            $remember_service
        );
    }

    /**
     *
     * Returns a new logout service instance.
     *
     * @param AdapterInterface $adapter The adapter to use with the service.
     *
     * @return Service\LogoutService
     *
     */
    public function newLogoutService(
        ?AdapterInterface $adapter = null,
        ?RememberService $remember_service = null
    ): Service\LogoutService {
        return new Service\LogoutService(
            $this->fixAdapter($adapter),
            $this->session,
            $remember_service
        );
    }

    /**
     *
     * Returns a new "resume session" service.
     *
     * @param AdapterInterface $adapter The adapter to use with the service, and
     * with the underlying logout service.
     *
     * @param int $idle_ttl The session idle time in seconds.
     *
     * @param int $expire_ttl The session expire time in seconds.
     *
     * @return Service\ResumeService
     *
     */
    public function newResumeService(
        ?AdapterInterface $adapter = null,
        $idle_ttl = 3600,               // 1 hour
        $expire_ttl = 86400,            // 24 hours
        ?RememberService $remember_service = null
    ): Service\ResumeService {

        $adapter = $this->fixAdapter($adapter);

        $timer = new Session\Timer(
            ini_get('session.gc_maxlifetime'),
            ini_get('session.cookie_lifetime'),
            $idle_ttl,
            $expire_ttl
        );

        // NOTE: the internal logout service is intentionally *not* given the
        // remember service. An idle/expired session must not discard the
        // remember-me token; that is exactly when we want it to re-establish
        // the session on the next request.
        $logout_service = new Service\LogoutService(
            $adapter,
            $this->session
        );

        return new Service\ResumeService(
            $adapter,
            $this->session,
            $timer,
            $logout_service,
            $remember_service
        );
    }

    /**
     *
     * Returns a new "remember me" service.
     *
     * @param RememberStorageInterface $storage Server-side token storage (for
     * example, from newPdoRememberStorage()).
     *
     * @param array $options Options for the service and cookie: `name` (cookie
     * name, default "remember"), `ttl` (token lifetime in seconds, default 30
     * days), `user_loader` (an optional `fn(string $username): ?array` to
     * re-fetch fresh user data on resume), and cookie params `path`, `domain`,
     * `secure`, `httponly`, `samesite`.
     *
     * @return RememberService
     *
     */
    public function newRememberService(
        RememberStorageInterface $storage,
        array $options = array()
    ): RememberService {
        $phpfunc = new Phpfunc;
        $name = isset($options['name']) ? $options['name'] : 'remember';
        $ttl = isset($options['ttl']) ? $options['ttl'] : 2592000; // 30 days
        $user_loader = isset($options['user_loader']) ? $options['user_loader'] : null;

        return new Remember\RememberService(
            $storage,
            $this->session,
            new Remember\Token($phpfunc),
            new Remember\Cookie($phpfunc, $this->cookie, $options),
            $name,
            $ttl,
            $user_loader
        );
    }

    /**
     *
     * Returns a new PDO-backed remember-me token storage.
     *
     * @param PDO $pdo A PDO connection.
     *
     * @param string $table The table holding the tokens.
     *
     * @return Remember\PdoRememberStorage
     *
     */
    public function newPdoRememberStorage(PDO $pdo, $table = 'aura_auth_remember'): Remember\PdoRememberStorage
    {
        return new Remember\PdoRememberStorage($pdo, $table);
    }

    /**
     *
     * Returns a new login-throttling policy service.
     *
     * @param ThrottleStorageInterface $storage The failure-counter storage (for
     * example, from newPdoThrottleStorage() or newRedisThrottleStorage()).
     *
     * @param array $options Policy options: `max_attempts` (failures allowed
     * before backoff, default 5) and `cap` (maximum backoff in seconds, default
     * 15 minutes).
     *
     * @return ThrottleService
     *
     */
    public function newThrottleService(
        ThrottleStorageInterface $storage,
        array $options = array()
    ): ThrottleService {
        return new Throttle\ThrottleService($storage, $options);
    }

    /**
     *
     * Wraps an adapter with brute-force login throttling.
     *
     * @param AdapterInterface $adapter The adapter to wrap.
     *
     * @param ThrottleService $throttle The throttling policy.
     *
     * @return Adapter\ThrottleAdapter
     *
     */
    public function newThrottleAdapter(
        AdapterInterface $adapter,
        ThrottleService $throttle
    ): Adapter\ThrottleAdapter {
        return new Adapter\ThrottleAdapter($adapter, $throttle);
    }

    /**
     *
     * Returns a new PDO-backed throttle storage.
     *
     * @param PDO $pdo A PDO connection.
     *
     * @param string $table The table holding the failure rows.
     *
     * @param int $window How long a failure is remembered, in seconds.
     *
     * @return Throttle\PdoThrottleStorage
     *
     */
    public function newPdoThrottleStorage(
        PDO $pdo,
        $table = 'aura_auth_throttle',
        $window = 900
    ): Throttle\PdoThrottleStorage {
        return new Throttle\PdoThrottleStorage($pdo, $table, $window);
    }

    /**
     *
     * Returns a new Redis-backed throttle storage.
     *
     * @param RedisClientInterface|object $client A RedisClientInterface, or a
     * raw phpredis `\Redis` / `Predis\Client` which is wrapped in a
     * NativeRedisClient automatically.
     *
     * @param string $prefix A key prefix namespacing throttle data.
     *
     * @param int $window How long a failure is remembered, in seconds.
     *
     * @return Throttle\RedisThrottleStorage
     *
     */
    public function newRedisThrottleStorage(
        $client,
        $prefix = 'aura_auth_throttle:',
        $window = 900
    ): Throttle\RedisThrottleStorage {
        if (! $client instanceof RedisClientInterface) {
            $client = new Throttle\NativeRedisClient($client);
        }
        return new Throttle\RedisThrottleStorage($client, $prefix, $window);
    }

    /**
     *
     * Returns a new OAuth 2.0 adapter.
     *
     * @param ProviderInterface $provider The OAuth 2.0 provider seam (for
     * example, an OAuth\LeagueProvider).
     *
     * @param array $options Mapping options: `username_field` and/or `map`.
     *
     * @return Adapter\OAuth2Adapter
     *
     */
    public function newOAuth2Adapter(ProviderInterface $provider, array $options = []): Adapter\OAuth2Adapter
    {
        return new Adapter\OAuth2Adapter($provider, $options);
    }

    /**
     *
     * Returns a new OAuth 2.0 authorization-code flow helper, wired to this
     * factory's session segment for anti-CSRF state and PKCE persistence.
     *
     * @param ProviderInterface $provider The OAuth 2.0 provider seam.
     *
     * @param array $options Optional key overrides: `state_key`,
     * `verifier_key`.
     *
     * @return OAuth\AuthorizationCodeFlow
     *
     */
    public function newOAuth2Flow(ProviderInterface $provider, array $options = []): OAuth\AuthorizationCodeFlow
    {
        return new OAuth\AuthorizationCodeFlow($provider, $this->segment, $options);
    }

    /**
     *
     * Make sure we have an Adapter instance, even if only a NullAdapter.
     *
     * @param Adapterinterface $adapter Check to make sure this is an Adapter
     * instance.
     *
     * @return AdapterInterface
     *
     */
    protected function fixAdapter(?AdapterInterface $adapter = null): AdapterInterface
    {
        if ($adapter === null) {
            $adapter = new Adapter\NullAdapter;
        }
        return $adapter;
    }

    /**
     *
     * Returns a new PDO adapter.
     *
     * @param PDO $pdo A PDO connection.
     *
     * @param mixed $verifier_spec Specification to pick a verifier: if an
     * object, assume a VerifierInterface; otherwise, assume a PASSWORD_*
     * constant for a PasswordVerifier.
     *
     * @param array $cols Select these columns.
     *
     * @param string $from Select from this table (and joins).
     *
     * @param string $where WHERE conditions for the select.
     *
     * @return Adapter\PdoAdapter
     *
     */
    public function newPdoAdapter(
        PDO $pdo,
        $verifier_spec,
        array $cols,
        $from,
        $where = null
    ): Adapter\PdoAdapter {
        if (is_object($verifier_spec)) {
            $verifier = $verifier_spec;
        } else {
            $verifier = new Verifier\PasswordVerifier($verifier_spec);
        }

        return new Adapter\PdoAdapter(
            $pdo,
            $verifier,
            $cols,
            $from,
            $where
        );
    }

    /**
     *
     * Returns a new HtpasswdAdapter.
     *
     * @param string $file Path to the htpasswd file.
     *
     * @return Adapter\HtpasswdAdapter
     *
     */
    public function newHtpasswdAdapter($file): Adapter\HtpasswdAdapter
    {
        $verifier = new Verifier\HtpasswdVerifier;
        return new Adapter\HtpasswdAdapter(
            $file,
            $verifier
        );
    }

    /**
     *
     * Returns a new ImapAdapter.
     *
     * @param string $mailbox An imap_open() mailbox string.
     *
     * @param int $options Options for the imap_open() call.
     *
     * @param int $retries Try to connect this many times.
     *
     * @param array $params Set these params after opening the connection.
     *
     * @return Adapter\ImapAdapter
     *
     */
    public function newImapAdapter(
        $mailbox,
        $options = 0,
        $retries = 1,
        ?array $params = null
    ): Adapter\ImapAdapter {
        return new Adapter\ImapAdapter(
            new Phpfunc,
            $mailbox,
            $options,
            $retries,
            $params
        );
    }

    /**
     *
     * Returns a new LdapAdapter.
     *
     * @param string $server An LDAP server string.
     *
     * @param string $dnformat A distinguished name format string for looking up
     * the username.
     *
     * @param array $options Use these connection options.
     *
     * @param array $search Optional "bind, search, rebind" configuration
     * (keys: `binddn`, `bindpw`, `basedn`, `filter`, and optionally
     * `attributes`). When given, the adapter binds with the service account
     * and searches for the user instead of binding directly with `$dnformat`.
     *
     * @return Adapter\LdapAdapter
     *
     */
    public function newLdapAdapter(
        $server,
        $dnformat,
        array $options = array(),
        array $search = array()
    ): Adapter\LdapAdapter {
        return new Adapter\LdapAdapter(
            new Phpfunc,
            $server,
            $dnformat,
            $options,
            $search
        );
    }
}
