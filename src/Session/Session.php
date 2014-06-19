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
namespace Aura\Auth\Session;

/**
 *
 * Session manager.
 *
 * @package Aura.Auth
 *
 */
class Session implements SessionInterface
{
    /**
     *
     * @var array
     *
     */
    protected $cookies;

    /**
     *
     * @param array $cookies
     *
     */
    public function __construct(array $cookies)
    {
        $this->cookies = $cookies;
    }

    /**
     *
     * Start Session
     *
     * @return bool
     *
     */
    public function start()
    {
        return session_start();
    }

    /**
     *
     * Resume previous session
     *
     * @return bool
     *
     */
    public function resume()
    {
        if (session_id() !== '') {
            return true;
        }

        if (isset($this->cookies[session_name()])) {
            return $this->start();
        }

        return false;
    }

    /**
     *
     * Re generate session id
     *
     * @return mixed
     *
     */
    public function regenerateId()
    {
        return session_regenerate_id(true);
    }

    /**
     *
     * Destroy session.
     *
     * @todo Need more thorough destruction?
     * cf. <http://php.net/session_destroy>
     */
    public function destroy()
    {
        return session_destroy();
    }
}
