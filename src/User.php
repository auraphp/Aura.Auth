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
        Timer $timer
    ) {
        $this->session = $session;
        $this->segment = $segment;
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
    public function resumeSession()
    {
        if (! $this->session->resume()) {
            return false;
        }

        $timeout_status = $this->timer->getTimeoutStatus(
            $this->getFirstActive(),
            $this->getLastActive()
        );

        if ($timeout_status) {
            $this->setStatus($timeout_status);
            return true;
        }

        $this->setLastActive(time());
        return true;
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
        $this->segment->set('status', $status);
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
        return $this->segment->get('status', Status::ANON);
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
        $this->segment->set('first_active', $first_active);
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
        return $this->segment->get('first_active');
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
        $this->segment->set('last_active', $last_active);
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
        return $this->segment->get('last_active');
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
        $this->segment->set('name', $name);
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
        return $this->segment->get('name');
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
        $this->segment->set('data', $data);
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
        return $this->segment->get('data', array());
    }
}
