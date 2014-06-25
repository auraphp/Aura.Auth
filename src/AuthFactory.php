<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @package Aura.Auth
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Auth;

use Aura\Auth\Adapter;
use Aura\Auth\Service;
use Aura\Auth\Session;
use Aura\Auth\Verifier;
use Aura\Auth\Adapter\AdapterInterface;
use PDO;

/**
 *
 * Auth Factory
 *
 * @package Aura.Auth
 *
 */

class AuthFactory
{
    /**
     * cookie
     *
     * @var mixed
     *
     */
    protected $cookie;

    public function __construct(array $cookie)
    {
        $this->cookie = $cookie;
    }

    /**
     *
     * Returns a new Auth object.
     *
     * @return Auth
     *
     */
    public function newInstance()
    {
        return new Auth(new Session\Segment);
    }

    /**
     *
     * Login Service
     *
     * @param AdapterInterface $adapter
     *
     * @return Service\LoginService
     *
     */
    public function newLoginService(AdapterInterface $adapter = null)
    {
        return new Service\LoginService(
            $this->fixAdapter($adapter),
            $this->newSession()
        );
    }

    /**
     *
     * Logout Service
     *
     * @param AdapterInterface $adapter
     *
     * @return Service\LogoutService
     *
     */
    public function newLogoutService(
        AdapterInterface $adapter = null
    ) {
        return new Service\LogoutService(
            $this->fixAdapter($adapter),
            $this->newSession()
        );
    }

    /**
     *
     * Resume if started else start a new session
     *
     * @param AdapterInterface $adapter
     *
     * @param int $idle_ttl
     *
     * @param int $expire_ttl
     *
     * @return Service\ResumeService
     *
     */
    public function newResumeService(
        AdapterInterface $adapter = null,
        $idle_ttl = 1440,
        $expire_ttl = 14400
    ) {
        $adapter = $this->fixAdapter($adapter);
        $session = $this->newSession();
        $timer = new Session\Timer(
            ini_get('session.gc_maxlifetime'),
            ini_get('session.cookie_lifetime'),
            $idle_ttl,
            $expire_ttl
        );

        $logout_service = new Service\LogoutService(
            $adapter,
            $session
        );

        return new Service\ResumeService(
            $adapter,
            $session,
            $timer,
            $logout_service
        );
    }

    /**
     *
     * Start a session
     *
     * @return Session\Session
     *
     */
    protected function newSession()
    {
        return new Session\Session($this->cookie);
    }

    /**
     *
     * If no adapter provided create Adapter\NullAdapter else
     * the same adapter passed
     *
     * @param Adapterinterface $adapter
     *
     * @return Adapterinterface
     *
     */
    protected function fixAdapter(AdapterInterface $adapter = null)
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
     * @param PDO $pdo
     *
     * @param string|object $verifier_spec
     *
     * @param array $cols
     *
     * @param string $from
     *
     * @param string $where
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
    ) {
        if (is_string($verifier_spec)) {
            $verifier = new Verifier\HashVerifier($verifier_spec);
        } elseif (is_int($verifier_spec)) {
            $verifier = new Verifier\PasswordVerifier($verifier_spec);
        } else {
            $verifier = $verifier_spec;
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
     * Returns a new Htpasswd adapter.
     *
     * @param string $file
     *
     * @return Adapter\HtpasswdAdapter
     *
     */
    public function newHtpasswdAdapter($file)
    {
        $verifier = new Verifier\HtpasswdVerifier;
        return new Adapter\HtpasswdAdapter(
            $file,
            $verifier
        );
    }
}
