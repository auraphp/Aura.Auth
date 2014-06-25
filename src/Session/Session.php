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
    protected $cookie;

    /**
     *
     * @param array $cookie
     *
     */
    public function __construct(array $cookie)
    {
        $this->cookie = $cookie;
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

        if (isset($this->cookie[session_name()])) {
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
     * @see http://php.net/session-destroy
     *
     */
    public function destroy()
    {
        $cookie = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $cookie['path'],
            $cookie['domain'],
            $cookie['secure'],
            $cookie['httponly']
        );
        return session_destroy();
    }
}
