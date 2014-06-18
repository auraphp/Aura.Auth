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
        $this->session = new Session($cookies);
        $this->segment = new Segment;
    }

    public function setSession(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function setSegment(SegmentInterface $segment)
    {
        $this->segment = $segment;
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
        $user = new User(
            $this->session,
            $this->segment,
            new Timer(
                $this->idle_ttl,
                $this->expire_ttl
            )
        );

        return new Auth(
            $this->adapter,
            $user,
        );
    }
}
