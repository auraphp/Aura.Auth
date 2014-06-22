<?php
namespace Aura\Auth\Handler;

use Aura\Auth\User;
use Aura\Auth\Adapter\AdapterInterface;
use Aura\Auth\Session\SessionInterface;
use Aura\Auth\Session\Timer;

class ResumeHandler
{
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
