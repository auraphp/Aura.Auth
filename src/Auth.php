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

use Aura\Auth\Adapter\AdapterInterface;
use Aura\Auth\User;

/**
 *
 * Authentication manager.
 *
 * @package Aura.Auth
 *
 */
class Auth
{
    /**
     *
     * A credential storage adapter.
     *
     * @var AdapterInterface
     *
     */
    protected $adapter;

    protected $error;

    /**
     *
     * The current user.
     *
     * @var User
     *
     */
    protected $user;

    /**
     *
     * Constructor.
     *
     * @param SessionInterface $manager A session manager.
     *
     * @param SegmentInterface $data A session data store.
     *
     * @param Timer $timer An idle/expire timer.
     *
     * @return self
     *
     */
    public function __construct(
        AdapterInterface $adapter,
        User $user
    ) {
        $this->adapter = $adapter;
        $this->user = $user;
    }

    public function getAdapter()
    {
        return $this->adapter;
    }

    public function getError()
    {
        return $this->error;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function init()
    {
        $logout = $this->user->resumeSession()
               && ($this->user->isIdle() || $this->user->isExpired());

        if ($logout) {
            $this->adapter->logout($this->user);
            $this->user->forceLogout($this->user->getStatus());
        }
    }

    /**
     *
     * Logs the user in via the adapter.
     *
     * On success, this will start the session and populate it with the user
     * and info returned by the adapter. On failure, it will populate
     * the error property with the error value reported by the adapter.
     *
     * @param mixed $cred The credentials to pass to the adapter.
     *
     * @return bool True on success, false on failure.
     *
     */
    public function login($cred)
    {
        $this->error = null;

        if ($this->adapter->login($cred)) {
            $this->user->forceLogin(
                $this->adapter->getUser(),
                $this->adapter->getInfo()
            );
            return true;
        }

        $this->error = $this->adapter->getError();
        return false;
    }

    /**
     *
     * Logs the user out via the adapter.
     *
     * @return bool True on success, false on failure.
     *
     */
    public function logout()
    {
        $this->error = null;

        if ($this->adapter->logout($this->user)) {
            $this->user->forceLogout();
            return true;
        }

        $this->error = $this->adapter->getError();
        return false;
    }
}
