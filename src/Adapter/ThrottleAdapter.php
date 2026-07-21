<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/MIT-license.php MIT
 *
 */
namespace Aura\Auth\Adapter;

use Aura\Auth\Auth;
use Aura\Auth\Exception;
use Aura\Auth\Status;
use Aura\Auth\Throttle\ThrottleService;

/**
 *
 * Wraps another adapter to add brute-force login throttling.
 *
 * Because every adapter shares the {@see AdapterInterface} contract, this
 * decorator gives throttling to any of them — PDO, htpasswd, LDAP, IMAP,
 * OAuth — without changes to the wrapped adapter or the login service. It keys
 * the throttle on the attempted user name: it asserts the attempt is allowed
 * before delegating, records a failure when the inner adapter throws, and
 * clears the counter on success.
 *
 * @package Aura.Auth
 *
 */
class ThrottleAdapter implements AdapterInterface
{
    /**
     *
     * The wrapped adapter.
     *
     * @var AdapterInterface
     *
     */
    protected $adapter;

    /**
     *
     * The throttling policy.
     *
     * @var ThrottleService
     *
     */
    protected $throttle;

    /**
     *
     * Constructor.
     *
     * @param AdapterInterface $adapter The adapter to wrap.
     *
     * @param ThrottleService $throttle The throttling policy.
     *
     */
    public function __construct(AdapterInterface $adapter, ThrottleService $throttle)
    {
        $this->adapter = $adapter;
        $this->throttle = $throttle;
    }

    /**
     *
     * Verifies credentials, throttling by the attempted user name.
     *
     * @param array $input Credential input.
     *
     * @return array An array of login data on success.
     *
     * @throws \Aura\Auth\Exception\ThrottleExceeded when the attempt is
     * currently blocked by backoff.
     *
     */
    public function login(array $input): array
    {
        $key = isset($input['username']) ? (string) $input['username'] : '';

        $this->throttle->assert($key);

        try {
            $result = $this->adapter->login($input);
        } catch (Exception $e) {
            $this->throttle->recordFailure($key);
            throw $e;
        }

        $this->throttle->reset($key);
        return $result;
    }

    /**
     *
     * {@inheritDoc}
     *
     */
    public function logout(Auth $auth, $status = Status::ANON): void
    {
        $this->adapter->logout($auth, $status);
    }

    /**
     *
     * {@inheritDoc}
     *
     */
    public function resume(Auth $auth): void
    {
        $this->adapter->resume($auth);
    }

    /**
     *
     * Passes through the wrapped adapter's rehash report, so that wrapping an
     * adapter for throttling does not quietly stop password migration.
     *
     * needsRehash() is not part of AdapterInterface -- adding it there would
     * break existing implementations -- so the wrapped adapter may not have
     * it; false in that case, meaning "nothing to report".
     *
     * @return bool
     *
     */
    public function needsRehash(): bool
    {
        if (! method_exists($this->adapter, 'needsRehash')) {
            return false;
        }

        return $this->adapter->needsRehash();
    }
}
