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

use Aura\Auth\User;
use Aura\Auth\Adapter\AdapterInterface;
use Aura\Auth\Session\SessionInterface;
use Aura\Auth\Session\Timer;

/**
 *
 * Resume Service
 *
 * @package Aura.Auth
 *
 */
class ResumeService extends AbstractService
{
    protected $timer;

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
        SessionInterface $session,
        AdapterInterface $adapter,
        Timer $timer
    ) {
        parent::__construct($user, $session, $adapter);
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
    public function resume()
    {
        $this->session->resume();
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
            $this->forceLogout($timeout_status);
            return true;
        }

        return false;
    }
}
