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
namespace Aura\Auth\Service;

use Aura\Auth\Adapter\AdapterInterface;
use Aura\Auth\Session\SessionInterface;
use Aura\Auth\Status;
use Aura\Auth\Auth;

/**
 *
 * Login handler
 *
 * @package Aura.Auth
 *
 */
class LoginService
{
    protected $adapter;

    protected $session;

    /**
     *
     *  @param Auth $auth
     *
     *  @param AdapterInterface $adapter
     *
     */
    public function __construct(
        AdapterInterface $adapter,
        SessionInterface $session
    ) {
        $this->adapter = $adapter;
        $this->session = $session;
    }

    /**
     *
     * Login user
     *
     * @see AdapterInterface::login
     *
     * @see Auth::forceLogin
     *
     * @param array $cred
     *
     * @return void
     */
    public function login(Auth $auth, array $cred)
    {
        list($name, $data) = $this->adapter->login($cred);
        $this->forceLogin($auth, $name, $data);
    }

    /**
     *
     * Forces a successful login.
     *
     * @param string $name The authenticated user name.
     *
     * @param string $data Additional arbitrary user data.
     *
     * @param string $status The new authentication status.
     *
     * @return string|false The authentication status on success, or boolean
     * false on failure.
     *
     */
    public function forceLogin(
        Auth $auth,
        $name,
        array $data = array(),
        $status = Status::VALID
    ) {
        $started = $this->session->resume() || $this->session->start();
        if (! $started) {
            return false;
        }

        $this->session->regenerateId();
        $auth->set(
            $status,
            time(),
            time(),
            $name,
            $data
        );

        return $status;
    }
}
