<?php
/**
 * 
 * This file is part of the Aura project for PHP.
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 */
namespace Aura\Auth;

use Aura\Auth\Exception\MissingOption;

/**
 * 
 * 
 * 
 * @package Aura.Auth
 * 
 */
class Auth
{
    /**
     * 
     * The user is anonymous / unauthenticated (no attempt has been made to 
     * authenticate).
     * 
     * @const string
     * 
     */
    const ANON = 'AURA\AUTH\AUTH\ANON';
    
    /**
     * 
     * The max time for authentication has expired.
     * 
     * @const string
     * 
     */
    const EXPIRED = 'AURA\AUTH\AUTH\EXPIRED';
    
    /**
     * 
     * The authenticated user has been idle for too long.
     * 
     * @const string
     * 
     */
    const IDLED = 'AURA\AUTH\AUTH\IDLED';
    
    /**
     * 
     * The user is authenticated and has not timed out.
     * 
     * @const string
     * 
     */
    const VALID = 'AURA\AUTH\AUTH\VALID';
    
    /**
     * 
     * The user attempted authentication but failed.
     * 
     * @const string
     * 
     */
    const WRONG = 'AURA\AUTH\AUTH\WRONG';


    /**
     * 
     * @var AdapterFactory 
     * 
     */
    protected $factory;

    /**
     *
     * Authentication lifetime in seconds; zero is
     * forever.  Default is 14400 (4 hours). If this value is greater than
     * the non-zero PHP ini setting for `session.cookie_lifetime`, it will
     * throw an exception
     * 
     * @var integer
     *
     */
    protected $expire = 14400;

    /**
     * 
     * Maximum allowed idle time in seconds; zero is
     * forever.  Default is 1440 (24 minutes). If this value is greater than
     * the the PHP ini setting for `session.gc_maxlifetime`, it will throw
     * an exception.
     *  
     * @var integer
     *
     */
    protected $idle = 1440;

    /**
     * 
     * @var Aura\Auth\User
     * 
     */
    protected $user;

    protected $status;


    /**
     * 
     * @param array $adapters List of available Auth adapters. Format:
     * adapter_name => function () { return new Adapter(...); },
     * 
     */
    public function __construct(AdapterFactory $adapter_factory)
    {
        if (! isset($_SESSION)) {
            session_start();
            session_regenerate_id();
        }

        // check max life before garbage collection on server vs. idle time
        $gc_maxlife = ini_get('session.gc_maxlifetime');

        if ($gc_maxlife < $this->idle) {

            $msg = "PHP setting `session.gc_maxlifetime` is " .
                   "less than `Auth::\$idle`.";

            throw new Exception($msg);
        }
        
        // check life at client vs. exipire time;
        // if life at client is zero, cookie never expires.
        $cookie_life = ini_get('session.cookie_lifetime');

        if ($cookie_life > 0 && $cookie_life < $this->expire) {

            $msg = "PHP setting `session.cookie_lifetime` is " .
                   "less than `Auth::\$expire`.";

            throw new Exception($msg);
        }

        $this->factory = $adapter_factory;

        $this->loadUser();
        $this->updateIdleExpire();
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getStatus()
    {
        return $this->status;
    }

    /**
     * 
     * Is the current user authenticated.
     * 
     * @return boolean 
     * 
     */
    public function isValid()
    {
        return self::VALID == $this->status;
    }

    /**
     * 
     * Validate a users credentials.
     *
     * @param string  $adapter_name 
     * 
     * @param  array  $opts adapter options
     * 
     * @return boolean 
     *
     * @throws Exception If $adapter_name does not exist.
     *
     * @throws Exception If the factory can not find the adapter.
     * 
     * @throws MissingOption If the adapter is missing a required option
     * i.e. missing username or password.
     *      
     */
    public function validate($adapter_name, array $opts)
    {
        $adapter = $this->factory->newInstance($adapter_name);

        try {
            $result = $adapter->authenticate($opts);
        
        } catch (Exception $e) {
            throw new MissingOption($e->getMessage());
        }

        if ($result instanceOf User) {
            $this->reset(self::VALID, $result);

        } else {
            $this->reset(self::WRONG);
        }

        return $this->isValid();
    }

    /**
     * 
     * Resets any authentication data in the session.
     * 
     * Typically used for idling, expiration, and logout.  Calls
     * [[php::session_regenerate_id() | ]] to clear previous session, if any
     * exists.
     * 
     * @param string $status An Auth status string; default is Auth::ANON.
     * 
     * @param User $info If status is Auth::VALID, populate properties with this
     * user data, with keys for 'handle', 'email', 'moniker', 'uri', and 
     * 'uid'.  If a key is empty or does not exist, its value is set to null.
     * 
     * @return void
     * 
     */
    public function reset($status = self::ANON, User $info = null)
    {
        // canonicalize the status value
        $status = strtoupper($status);
        
        if (($this->status == self::ANON) && ($status == self::ANON)) {
            // If we are transitioning between anonymous and anonymous,
            // don't attempt to store information which would trigger
            // a session to start
            return null; // return null to make testing easer
        }
        
        // change the current status
        $this->status = $status;

        // change properties
        if ($this->status == self::VALID) {
            // update the timers, leave user info alone
            $now = time();
            $_SESSION[__CLASS__]['initial'] = $now;
            $_SESSION[__CLASS__]['active']  = $now;
        } else {
            // clear the timers *and* the user info
            $_SESSION[__CLASS__]['initial'] = null;
            $_SESSION[__CLASS__]['active']  = null;
            $info = null;
        }
        
        $this->setInfo($info);
                
        // reset the session id and delete previous session
        session_regenerate_id();
    }

    /**
     * 
     * Updates idle and expire times, invalidating authentication if
     * they are exceeded.
     * 
     * Note that if your script runs more than 1 second, it is possible
     * that multiple calls to this method may result in the authentication
     * expiring in the middle of the script.  As such, if you only need
     * to check that the user is logged in, call $this->isValid().
     * 
     * @return bool Whether or not authentication is still valid.
     * 
     */
    public function updateIdleExpire()
    {
        // is the current user already authenticated?
        if ($this->isValid()) {
            
            // Check if authentication has expired
            $tmp = $_SESSION[__CLASS__]['initial'] + $this->expire;

            if ($this->expire > 0 && $tmp < time()) {
                // past the expiration time
                $this->reset(self::EXPIRED);
                return false;
            }
    
            // Check if user has been idle for too long
            $tmp = $_SESSION[__CLASS__]['active'] + $this->idle;

            if ($this->idle > 0 && $tmp < time()) {
                // past the idle time
                $this->reset(self::IDLED);
                return false;
            }
            
            // not expired, not idled, so update the active time
            $_SESSION[__CLASS__]['active'] = time();
            return true;
            
        }
        
        return false;
    }

    /**
     * 
     * Set the $user variable and add the users unique id and adapter name
     * to a session so the user object can be found between requests.
     * 
     * @param User|null $user 
     * 
     */
    protected function setInfo($user)
    {
        if ($user instanceOf User) {

            $this->user                  = $user;
            $_SESSION[__CLASS__]['user'] = $user;//serialize($user);
            return;
        }

        $this->user = null;
        unset($_SESSION[__CLASS__]);
    }

    /**
     * 
     * Load an already authenticated user. 
     * 
     * @return void
     * 
     */
    protected function loadUser()
    {
        if (empty($_SESSION[__CLASS__]['user'])    ||
            empty($_SESSION[__CLASS__]['initial']) ||
            empty($_SESSION[__CLASS__]['active'])) {

            unset($_SESSION[__CLASS__]);
            $this->reset(self::ANON);
            return;
        }

        $result       = $_SESSION[__CLASS__]['user'];//unserialize($_SESSION[__CLASS__]['user']);

        $this->reset(self::VALID, $result);
    }
}