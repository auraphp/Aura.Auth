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

use Aura\Auth\Session\SegmentInterface;
use Aura\Auth\Session\SessionInterface;

/**
 *
 * The current user (authenticated or otherwise).
 *
 * @package Aura.Auth
 *
 */
class User
{
    /**
     *
     * A session manager.
     *
     * @var Session
     *
     */
    protected $session;

    /**
     *
     * Session data.
     *
     * @var SegmentInterface
     *
     */
    protected $segment;

    /**
     *
     * A idle/expire timer.
     *
     * @var Timer
     *
     */
    protected $timer;

    /**
     *
     * Constructor.
     *
     * @param SessionInterface $session A session session.
     *
     * @param SegmentInterface $segment A session data store.
     *
     * @return self
     *
     */
    public function __construct(
        SessionInterface $session,
        SegmentInterface $segment,
        $expire_ttl = null,
        $idle_ttl = null
    ) {
        $this->session = $session;
        $this->segment = $segment;
        $this->setExpireTtl($expire_ttl);
        $this->setIdleTtl($idle_ttl);
    }

    /**
     *
     * Resumes any previous session, logging out the user as idled or
     * expired if needed.
     *
     * @return bool Whether or not a session still exists.
     *
     * @see checkIdle()
     *
     * @see checkExpired()
     *
     */
    public function resumeSession()
    {
        if (! $this->session->resume()) {
            return false;
        }

        if (! $this->checkIdle() && ! $this->checkExpired()) {
            $this->setLastActive(time());
        }

        return true;
    }

    protected function checkIdle()
    {
        $idle = $this->idle_ttl <= 0
             || ($this->getLastActive() + $this->idle_ttl) < time();
        if ($idle) {
            $this->setStatus(Status::IDLE);
            return true;
        }
        return false;
    }

    protected function checkExpired()
    {
        $expired = $this->expire_ttl <= 0
                || ($this->getFirstActive() + $this->expire_ttl) < time();
        if ($expired) {
            $this->setStatus(Status::EXPIRED);
            return true;
        }
        return false;
    }

    /**
     *
     * Forces a successful login, bypassing the adapter.
     *
     * @param string $name The authenticated user name.
     *
     * @param string $data Additional arbitrary user data.
     *
     * @return string|false
     *
     */
    public function forceLogin($name, array $data = array())
    {
        $started = $this->session->resume() || $this->session->start();
        if (! $started) {
            return false;
        }

        $this->session->regenerateId();
        return $this->set(
            Status::VALID,
            time(),
            time(),
            $name,
            $data
        );
    }

    /**
     *
     * Forces a successful logout, bypassing the adapter.
     *
     * @return string|false
     *
     */
    public function forceLogout($status = Status::ANON)
    {
        $this->session->regenerateId();
        if (! $this->session->destroy()) {
            return false;
        }

        return $this->set(
            $status,
            null,
            null,
            null,
            array()
        );
    }

    protected function set(
        $status,
        $first_active,
        $last_active,
        $name,
        array $data
    ) {
        $this->setStatus($status);
        $this->setFirstActive($first_active);
        $this->setLastActive($last_active);
        $this->setName($name);
        $this->setData($data);
        return $status;
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
        $gc_maxlifetime = ini_get('session.max_lifetime');

        if ($idle_ttl === null) {
            $idle_ttl = $gc_maxlifetime;
        }

        if ($gc_maxlifetime < $idle_ttl) {
            throw new Exception(
                'session.gc_maxlifetime less than idle time'
            );
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
        $cookie_lifetime = ini_get('session.cookie_lifetime');

        if ($expire_ttl === null) {
            $expire_ttl = $cookie_lifetime;
        }

        if ($cookie_lifetime > 0 && $cookie_lifetime < $expire_ttl) {
            throw new Exception(
                'session.cookie_lifetime less than expire time'
            );
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
     * Is the user authenticated?
     *
     * @return bool
     *
     */
    public function isValid()
    {
        return $this->getStatus() == Status::VALID;
    }

    /**
     *
     * Is the user anonymous?
     *
     * @return bool
     *
     */
    public function isAnon()
    {
        return $this->getStatus() == Status::ANON;
    }

    /**
     *
     * Has the user been idle for too long?
     *
     * @return bool
     *
     */
    public function isIdle()
    {
        return $this->getStatus() == Status::IDLE;
    }

    /**
     *
     * Has the authentication time expired?
     *
     * @return bool
     *
     */
    public function isExpired()
    {
        return $this->getStatus() == Status::EXPIRED;
    }

    /**
     *
     * Sets the current authentication status.
     *
     * @param string $status
     *
     */
    public function setStatus($status)
    {
        $this->segment->status = $status;
    }

    /**
     *
     * Gets the current authentication status.
     *
     * @return string
     *
     */
    public function getStatus()
    {
        $status = $this->segment->status;
        if (! $status) {
            $status = Status::ANON;
        }
        return $status;
    }

    /**
     *
     * Sets the initial authentication time.
     *
     * @param int $first_active
     *
     */
    public function setFirstActive($first_active)
    {
        $this->segment->first_active = $first_active;
    }

    /**
     *
     * Gets the initial authentication time.
     *
     * @return int
     *
     */
    public function getFirstActive()
    {
        return $this->segment->first_active;
    }

    /**
     *
     * Sets the last active time.
     *
     * @param int $last_active
     *
     */
    public function setLastActive($last_active)
    {
        $this->segment->last_active = $last_active;
    }

    /**
     *
     * Gets the last active time.
     *
     * @return int
     *
     */
    public function getLastActive()
    {
        return $this->segment->last_active;
    }

    /**
     *
     * Sets the current user name.
     *
     * @param string $name
     *
     */
    public function setName($name)
    {
        $this->segment->name = $name;
    }

    /**
     *
     * Gets the current user name.
     *
     * @return string
     *
     */
    public function getName()
    {
        return $this->segment->name;
    }

    /**
     *
     * Sets the current user data.
     *
     * @param array $data
     *
     */
    public function setData(array $data)
    {
        $this->segment->data = $data;
    }

    /**
     *
     * Gets the current user data.
     *
     * @return array
     *
     */
    public function getData()
    {
        $data = $this->segment->data;
        if (! $data) {
            $data = array();
        }
        return $data;
    }
}
