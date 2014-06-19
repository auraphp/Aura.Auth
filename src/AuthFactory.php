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

use Aura\Auth\Session\Session;
use Aura\Auth\Session\SessionInterface;
use Aura\Auth\Session\Segment;
use Aura\Auth\Session\SegmentInterface;


/**
 *
 * Auth Factory
 *
 * @package Aura.Auth
 *
 */

class AuthFactory
{
    /**
     * manager
     *
     * @var SessionManager
     *
     */
    private $manager;


    /**
     * data
     *
     * @var SessionDataNative
     *
     */
    private $data;

    /**
     *
     * Maximum idle time in seconds; zero is forever.
     *
     * @var int
     *
     */
    private $idle_ttl;

    /**
     *
     * Maximum authentication lifetime in seconds; zero is forever.
     *
     * @var int
     *
     */
    private $expire_ttl;

    /**
     *
     * @param array $cookies
     *
     * @return void
     *
     */
    public function __construct(array $cookies)
    {
        $this->session = new Session($cookies);
        $this->segment = new Segment;
    }

    /**
     *
     * Set SessionManager
     *
     * @param SessionManagerInterface $manager
     *
     * @return void
     *
     */
    public function setSession(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     *
     * Set SessionData
     *
     * @param SessionDataInterface $data
     *
     * @return void
     *
     */
    public function setSegment(SegmentInterface $segment)
    {
        $this->segment = $segment;
    }

    /**
     *
     * Sets the maximum idle time.
     *
     * @param int $idle_ttl The maximum idle time in seconds.
     *
     * @return void
     *
     */
    public function setIdleTtl($idle_ttl)
    {
        $this->idle_ttl = $idle_ttl;
    }

    /**
     *
     * Sets the maximum authentication lifetime.
     *
     * @param int $expire_ttl The maximum authentication lifetime in seconds.
     *
     * @return void
     *
     */
    public function setExpireTtl($expire_ttl)
    {
        $this->expire_ttl = $expire_ttl;
    }

    /**
     *
     * Create an instance of Auth object
     *
     * @param AdapterInterface $adapter
     *
     * @return void
     *
     */
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
            $user
        );
    }
}
