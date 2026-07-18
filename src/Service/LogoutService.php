<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Auth\Service;

use Aura\Auth\Adapter\AdapterInterface;
use Aura\Session_Interface\SessionInterface;
use Aura\Auth\Remember\RememberService;
use Aura\Auth\Status;
use Aura\Auth\Auth;

/**
 *
 * Logout handler.
 *
 * @package Aura.Auth
 *
 */
class LogoutService
{
    /**
     *
     * A credential storage adapter.
     *
     * @var AdapterInterface
     *
     */
    protected $adapter;

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
     * An optional "remember me" handler.
     *
     * @var RememberService|null
     *
     */
    protected $remember_service;

    /**
     *
     * Constructor.
     *
     * @param AdapterInterface $adapter A credential storage adapter.
     *
     * @param SessionInterface $session A session manager.
     *
     * @param RememberService $remember_service An optional "remember me"
     * handler; when present, its token is discarded on logout.
     *
     */
    public function __construct(
        AdapterInterface $adapter,
        SessionInterface $session,
        ?RememberService $remember_service = null
    ) {
        $this->adapter = $adapter;
        $this->session = $session;
        $this->remember_service = $remember_service;
    }

    /**
     *
     * Log the user out via the adapter.
     *
     * @param Auth $auth An authentication tracker.
     *
     * @param string $status The status after logout.
     *
     * @return null
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
     * @param Auth $auth An authentication tracker.
     *
     * @param string $status The status after logout.
     *
     * @return string The new authentication status.
     *
     */
    public function forceLogout(Auth $auth, $status = Status::ANON)
    {
        if ($this->remember_service) {
            $this->remember_service->forget($auth);
        }

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
