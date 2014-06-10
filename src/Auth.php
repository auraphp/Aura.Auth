<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @package Aura.Autoload
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Auth;

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
     * The last error reported by the adapter system.
     *
     * @var mixed
     *
     */
    protected $error;

    /**
     *
     * A session manager.
     *
     * @var SessionInterface
     *
     */
    protected $error;

    /**
     *
     * Constructor.
     *
     * @param AdapterInterface $adapter A credential adapter object.
     *
     * @param SessionInterface $session A session manager.
     *
     * @param int $idle_ttl The maximum idle time in seconds.
     *
     * @param int $expire_ttl The maximum authentication time in seconds.
     *
     * @return self
     *
     */
    public function _construct(
        Adapter $adapter,
        Session $session,
        $idle_ttl = 1440,
        $expire_ttl = 14400
    ) {
        $this->adapter = $adapter;
        $this->session = $session;
        $this->setIdleTtl($idle_ttl);
        $this->setExpireTtl($expire_ttl);
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
        $this->idle_ttl = $idle_ttl;
        $gc_maxlifetime = ini_get('session.gc_maxlifetime');
        if ($gc_maxlifetime < $this->idle_ttl) {
            throw new Exception('gc_maxlifetime less than idle time');
        }
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
        $this->expire_ttl = $expire_ttl;
        $cookie_life = ini_get('session.cookie_lifetime');
        if ($cookie_life > 0 && $cookie_life < $this->expire_ttl) {
            throw new Exception('cookie_lifetime less than expire time');
        }
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

        if ($this->hasExpired()) {
            $this->logout(static::EXPIRE);
            return false;
        }

        if ($this->hasIdled()) {
            $this->logout(static::IDLE);
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
        return $this->getStatus() != static::VALID;
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
        return $this->getStatus() == static::VALID;
    }

    /**
     *
     * Has the authentication time expired?
     *
     * @return bool
     *
     */
    public function hasExpired()
    {
        return $this->expire_ttl > 0
            && ($this->session->initial + $this->expire_ttl) < time()
    }

    /**
     *
     * Has the idle time been exceeded?
     *
     * @return bool
     *
     */
    public function hasIdled()
    {
        return $this->idle_ttl > 0
            && ($this->session->active + $this->idle_ttl) < time();
    }

    /**
     *
     * Authenticate by passing credentials to the adapter system.
     *
     * On success, this will start the session and populate it with the user
     * and info returned by the adapter system. On failure, it will populate
     * the error property with the error value reported by the adapter system.
     *
     * @param mixed $creds The credentials to pass to the adapter system.
     *
     * @return bool True on success, false on failure.
     *
     */
    public function login($creds)
    {
        $this->error = null;

        $success = $this->adapter->login($creds);
        if ($success) {
            $this->forceLogin(
                $this->adapter->getUser(),
                $this->adapter->getInfo()
            );
            return true;
        }

        $this->setStatus(static::ERROR);
        $this->error = $this->adapter->getError();
        return false;
    }

    /**
     *
     * Forces a successful authentication, bypassing the adapter system.
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
        $this->session->start();
        $this->setStatus(static::VALID);
        $this->session->user = $user;
        $this->session->info = $info;
        $now = time();
        $this->session->initial = $now;
        $this->session->active = $now;
    }

    public function logout($status = static::ANON)
    {
        $this->error = null;

        $success = $this->adapter->logout(
            $this->getUser(),
            $this->getInfo()
        );

        if ($success) {
            $this->forceLogout(static::ANON);
            return true;
        }

        $this->setStatus(static::ERROR);
        $this->error = $this->adapter->getError();
        return false;
    }

    public function forceLogout($status = static::ANON)
    {
        $this->setSatus($status);
        unset($this->session->user);
        unset($this->session->info);
        unset($this->session->initial);
        unset($this->session->active);
    }

    protected function setStatus($status)
    {
        $old = $this->getStatus();
        $this->session->status = strtoupper($status);
        if ($this->session->status != $old) {
            $this->session->regenerateId();
        }
    }

    public function getStatus()
    {
        $status = $this->session->status;
        if (! $status) {
            $status = static::ANON;
        }
        return $status;
    }

    public function getInitial()
    {
        return $this->session->initial;
    }

    public function getActive()
    {
        return $this->session->active;
    }

    public function getUser()
    {
        return $this->session->user;
    }

    public function getInfo()
    {
        $info = $this->session->info;
        if (! $info) {
            $info = array();
        }
        return $info;
    }

    public function hasError()
    {
        return (bool) $this->error;
    }

    public function getError()
    {
        return $this->error;
    }
}
