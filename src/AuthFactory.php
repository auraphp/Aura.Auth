<?php
namespace Aura\Auth;

use Aura\Auth\Session\SessionManager;
use Aura\Auth\Session\SessionManagerInterface;
use Aura\Auth\Session\SessionDataNative;
use Aura\Auth\Session\SessionDataInterface;

class AuthFactory
{
    public function __construct(array $cookies)
    {
        $this->manager = new SessionManager($cookies);
        $this->data = new SessionDataNative;
    }

    public function setSessionManager(SessionManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function setSessionData(SessionDataInterface $data)
    {
        $this->data = $data;
    }

    public function setIdleTtl($idle_ttl)
    {
        $this->idle_ttl = $idle_ttl;
    }

    public function setExpireTtl($expire_ttl)
    {
        $this->expire_ttl = $expire_ttl;
    }

    public function newInstance()
    {
        return new Auth(
            $this->manager,
            $this->data,
            new Timer(
                $this->idle_ttl,
                $this->expire_ttl
            )
        );
    }
}
