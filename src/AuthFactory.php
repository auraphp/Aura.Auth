<?php
namespace Aura\Auth;

use Aura\Auth\Session\Session;
use Aura\Auth\Session\SessionInterface;
use Aura\Auth\Session\Segment;
use Aura\Auth\Session\SegmentInterface;

class AuthFactory
{
    public function __construct(array $cookies)
    {
        $this->manager = new Session($cookies);
        $this->data = new Segment;
    }

    public function setSession(SessionInterface $manager)
    {
        $this->manager = $manager;
    }

    public function setSessionData(SegmentInterface $data)
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

    public function newInstance(AdapterInterface $adapter)
    {
        return new Auth(
            $this->adapter,
            $this->manager,
            $this->data,
            new Timer(
                $this->idle_ttl,
                $this->expire_ttl
            )
        );
    }
}
