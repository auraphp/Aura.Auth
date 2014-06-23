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
namespace Aura\Auth\Handler;

use Aura\Auth\User;
use Aura\Auth\Adapter\AdapterInterface;
use Aura\Auth\Session\SessionInterface;
use Aura\Auth\Session\Timer;

/**
 *
 * Resume Handler
 *
 * @package Aura.Auth
 *
 */
class ResumeHandler
{
    /**
     *
     *  @param User $user
     *
     *  @param AdapterInterface $adapter
     *
     *  @param Timer $timer
     *
     */
    public function __construct(
        User $user,
        AdapterInterface $adapter,
        Timer $timer
    ) {
        $this->user = $user;
        $this->adapter = $adapter;
        $this->timer = $timer;
    }

    /**
     *
     * Resumes any previous session, logging out the user as idled or
     * expired if needed.
     *
     * @return bool Whether or not a session still exists.
     *
     */
    public function __invoke()
    {
        $this->user->getSession()->resume();
        if ($this->user->isAnon() || $this->timedOut()) {
            return;
        }

        $this->user->setLastActive(time());
        $this->adapter->resume($this->user);
    }

    /**
     *
     * Set the user timeout status, and force logout if expired
     *
     * @see User::logout
     *
     * @see AdapterInterface::forceLogout
     *
     * @return bool
     *
     */
    protected function timedOut()
    {
        $timeout_status = $this->timer->getTimeoutStatus(
            $this->user->getFirstActive(),
            $this->user->getLastActive()
        );

        if ($timeout_status) {
            $this->user->setStatus($timeout_status);
            $this->adapter->logout($this->user);
            $this->user->forceLogout($timeout_status);
            return true;
        }

        return false;
    }
}
