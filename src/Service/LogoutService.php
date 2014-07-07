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
 * Logout handler
 *
 * @package Aura.Auth
 *
 */
class LogoutService
{
    /**
     *
     * @var AdapterInterface
     *
     */
    protected $adapter;

    /**
     *
     * session
     *
     * @var SessionInterface
     *
     */
    protected $session;

    /**
     *
     *  @param Auth $auth
     *
     *  @param AdapterInterface $adapter
     *
     *  @param SessionInterface $adapter
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
     * Logout user
     *
     * @param Auth $auth
     *
     * @param string $status see Status class
     *
     */
    public function logout(Auth $auth, $status = Status::ANON)
    {
        $this->adapter->logout($auth, $status);
        $this->forceLogout($auth, $status);
    }

    /**
     *
     * Forces a successful logout.
     *
     * @param Auth $auth
     *
     * @param string $status The new authentication status.
     *
     * @return string The new authentication status.
     *
     */
    public function forceLogout(Auth $auth, $status = Status::ANON)
    {
        $this->session->regenerateId();

        $auth->set(
            $status,
            null,
            null,
            null,
            array()
        );

        return $status;
    }
}
