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
 * Native session data
 *
 * @package Aura.Auth
 *
 */
class SessionDataNative implements SessionDataInterface
{
    /**
     *
     * @var string
     *
     */
    protected $segment;

    /**
     *
     * @param bool $segment
     *
     */
    public function __construct($segment = 'Aura\Auth\Auth')
    {
        $this->segment = $segment;
    }

    /**
     *
     * @param mixed $key
     *
     * @return mixed
     *
     */
    public function __get($key)
    {
        if (isset($_SESSION[$this->segment][$key])) {
            return $_SESSION[$this->segment][$key];
        }
    }

    /**
     *
     * @param mixed $key
     *
     * @param mixed $val
     *
     * @return mixed
     *
     */
    public function __set($key, $val)
    {
        if (! isset($_SESSION)) {
            return;
        }

        $_SESSION[$this->segment][$key] = $val;
    }

    /**
     *
     * @param mixed $key
     *
     * @return bool
     *
     */
    public function __isset($key)
    {
        return isset($_SESSION[$this->segment][$key]);
    }

    /**
     *
     * @param mixed $key
     *
     * @return void
     *
     */
    public function __unset($key)
    {
        unset($_SESSION[$this->segment][$key]);
    }
}
