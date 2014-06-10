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
     * The user is anonymous/unauthenticated, with no current attempt to
     * authenticate.
     *
     * @const string
     *
     */
    const ANON = 'ANON';

    /**
     *
     * The max time for authentication has expired.
     *
     * @const string
     *
     */
    const EXPIRED = 'EXPIRED';

    /**
     *
     * The authenticated user has been idle for too long.
     *
     * @const string
     *
     */
    const IDLED = 'IDLED';

    /**
     *
     * The user is authenticated and has not idled or expired.
     *
     * @const string
     *
     */
    const VALID = 'VALID';

    /**
     *
     * There was an error when attempting authentication.
     *
     * @const string
     *
     */
    const ERROR = 'ERROR';

    /**
     *
     * A adapter object for checking credentials.
     *
     * @var AdapterInterface
     *
     */
    protected $adapter;

    /**
     *
     * The last error reported by the adapter.
     *
     * @var mixed
     *
     */
    protected $error;

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
     * @param AdapterInterface $adapter A credential adapter object.
     *
     * @param SessionInterface $session A session manager.
     *
     * @param Timer $timer An idle/expire timer.
     *
     * @return self
     *
     */
    public function __construct(
        AdapterInterface $adapter,
        SessionInterface $session,
        Timer $timer
    ) {
        $this->adapter = $adapter;
        $this->session = $session;
        $this->timer = $timer;
        $this->updateActive();
    }

    /**
     *
     * Returns the authentication adapter.
     *
     * @return AdapterInterface
     *
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     *
     * Returns the session manager.
     *
     * @return AdapterInterface
     *
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     *
     * Returns the timer.
     *
     * @return AdapterInterface
     *
     */
    public function getTimer()
    {
        return $this->timer;
    }

    /**
     *
     * Updates the "last active" time, logging out the user as idled or expired
     * if needed.
     *
     * Multiple calls to this method may result in idled or expired
     * authentication in the middle of script execution.
     *
     * @return bool Whether or not authentication is still valid.
     *
     * @see hasExpired()
     *
     * @see hasIdled()
     *
     * @see logout()
     *
     */
    public function updateActive()
    {
        if ($this->isAnon()) {
            return false;
        }

        if ($this->timer->hasExpired()) {
            $this->logout(self::EXPIRE);
            return false;
        }

        if ($this->timer->hasIdled()) {
            $this->logout(self::IDLE);
            return false;
        }

        $this->session->active = time();
        return true;
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
        return $this->getStatus() != self::VALID;
    }

    /**
     *
     * Is the user authentication valid?
     *
     * @return bool
     *
     */
    public function isValid()
    {
        return $this->getStatus() == self::VALID;
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

        $success = $this->adapter->login($cred);
        if ($success) {
            $this->forceLogin(
                $this->adapter->getUser(),
                $this->adapter->getInfo()
            );
            return true;
        }

        $this->setStatus(self::ERROR);
        $this->error = $this->adapter->getError();
        return false;
    }

    /**
     *
     * Forces a successful login, bypassing the adapter.
     *
     * @param string $user The authenticated user.
     *
     * @param string $info Information about the user.
     *
     * @return null
     *
     */
    public function forceLogin($user, $info = array())
    {
        $now = time();
        $this->session->start();
        $this->setStatus(self::VALID);
        $this->session->user = $user;
        $this->session->info = $info;
        $this->session->initial = $now;
        $this->session->active = $now;
    }

    /**
     *
     * Logs the user out via the adapter.
     *
     * @param string $status The status after successful logout.
     *
     * @return bool True on success, false on failure.
     *
     */
    public function logout($status = self::ANON)
    {
        $this->error = null;

        $success = $this->adapter->logout(
            $this->getUser(),
            $this->getInfo()
        );

        if ($success) {
            $this->forceLogout(self::ANON);
            return true;
        }

        $this->setStatus(self::ERROR);
        $this->error = $this->adapter->getError();
        return false;
    }

    /**
     *
     * Forces a successful logout, bypassing the adapter.
     *
     * @param string $status The status after logout.
     *
     * @return null
     *
     */
    public function forceLogout($status = self::ANON)
    {
        $this->setSatus($status);
        unset($this->session->user);
        unset($this->session->info);
        unset($this->session->initial);
        unset($this->session->active);
    }

    /**
     *
     * Sets the current status, regenerating the session ID on status changes.
     *
     * @param string $status The new status.
     *
     * @return null
     *
     */
    protected function setStatus($status)
    {
        $old = $this->getStatus();
        $this->session->status = strtoupper($status);
        if ($this->session->status != $old) {
            $this->session->regenerateId();
        }
    }

    /**
     *
     * Gets the current status.
     *
     * @return string
     *
     */
    public function getStatus()
    {
        $status = $this->session->status;
        if (! $status) {
            $status = self::ANON;
        }
        return $status;
    }

    /**
     *
     * Gets the initial authentication time.
     *
     * @return int
     *
     */
    public function getInitial()
    {
        return $this->session->initial;
    }

    /**
     *
     * Gets the last active time.
     *
     * @return int
     *
     */
    public function getActive()
    {
        return $this->session->active;
    }

    /**
     *
     * Gets the current user name.
     *
     * @return string
     *
     */
    public function getUser()
    {
        return $this->session->user;
    }

    /**
     *
     * Gets the current user information.
     *
     * @return array()
     *
     */
    public function getInfo()
    {
        $info = $this->session->info;
        if (! $info) {
            $info = array();
        }
        return $info;
    }

    /**
     *
     * Gets the last error reported by the adapter.
     *
     * @return mixed
     *
     */
    public function getError()
    {
        return $this->error;
    }
}
