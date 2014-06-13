<?php
namespace Aura\Auth;

class Timer
{
    protected $ini_gc_maxlifetime;

    protected $ini_cookie_lifetime;

    /**
     *
     * Maximum idle time in seconds; zero is forever.
     *
     * @var int
     *
     */
    protected $idle_ttl = 1440;

    /**
     *
     * Maximum authentication lifetime in seconds; zero is forever.
     *
     * @var int
     *
     */
    protected $expire_ttl = 14400;

    /**
     *
     * Constructor.
     *
     * @param int $idle_ttl The maximum idle time in seconds.
     *
     * @param int $expire_ttl The maximum authentication time in seconds.
     *
     * @return self
     *
     */
    public function __construct(
        $ini_gc_maxlifetime = 1440,
        $ini_cookie_lifetime = 0,
        $idle_ttl = 1440,
        $expire_ttl = 14400
    ) {
        $this->ini_gc_maxlifetime = $ini_gc_maxlifetime;
        $this->ini_cookie_lifetime = $ini_cookie_lifetime;
        $this->setIdleTtl($idle_ttl);
        $this->setExpireTtl($expire_ttl);
    }

    /**
     *
     * Sets the maximum idle time.
     *
     * @param int $idle_ttl The maximum idle time in seconds.
     *
     * @throws Exception when the session garbage collection max lifetime is
     * less than the idle time.
     *
     * @return null
     *
     */
    public function setIdleTtl($idle_ttl)
    {
        if ($this->ini_gc_maxlifetime < $idle_ttl) {
            throw new Exception('session.gc_maxlifetime less than idle time');
        }
        $this->idle_ttl = $idle_ttl;
    }

    /**
     *
     * Returns the maximum idle time.
     *
     * @return int
     *
     */
    public function getIdleTtl()
    {
        return $this->idle_ttl;
    }

    /**
     *
     * Sets the maximum authentication lifetime.
     *
     * @param int $expire_ttl The maximum authentication lifetime in seconds.
     *
     * @throws Exception when the session cookie lifetime is less than the
     * authentication lifetime.
     *
     * @return null
     *
     */
    public function setExpireTtl($expire_ttl)
    {
        $bad = $this->ini_cookie_lifetime > 0
            && $this->ini_cookie_lifetime < $expire_ttl;
        if ($bad) {
            throw new Exception('session.cookie_lifetime less than expire time');
        }
        $this->expire_ttl = $expire_ttl;
    }

    /**
     *
     * Returns the maximum authentication lifetime.
     *
     * @return int
     *
     */
    public function getExpireTtl()
    {
        return $this->expire_ttl;
    }

    /**
     *
     * Has the authentication time expired?
     *
     * @return bool
     *
     */
    public function hasExpired($initial)
    {
        return $this->expire_ttl <= 0
            || ($initial + $this->getExpireTtl()) < time();
    }

    /**
     *
     * Has the idle time been exceeded?
     *
     * @return bool
     *
     */
    public function hasIdled($active)
    {
        return $this->idle_ttl <= 0
            || ($active + $this->getIdleTtl()) < time();
    }
}
