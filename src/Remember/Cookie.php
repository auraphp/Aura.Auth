<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Auth\Remember;

use Aura\Auth\Phpfunc;

/**
 *
 * A thin, testable wrapper around cookie reads and writes for the "remember
 * me" token, applying hardened defaults (secure, httponly, SameSite).
 *
 * @package Aura.Auth
 *
 */
class Cookie
{
    /**
     *
     * A proxy for PHP functions (for testability of setcookie()).
     *
     * @var Phpfunc
     *
     */
    protected $phpfunc;

    /**
     *
     * A copy of the incoming $_COOKIE array.
     *
     * @var array
     *
     */
    protected $cookie;

    /**
     *
     * Cookie parameters applied to every write.
     *
     * @var array
     *
     */
    protected $options;

    /**
     *
     * Constructor.
     *
     * @param Phpfunc $phpfunc A proxy for PHP functions.
     *
     * @param array $cookie A copy of the $_COOKIE array.
     *
     * @param array $options Cookie parameter overrides (path, domain, secure,
     * httponly, samesite).
     *
     */
    public function __construct(Phpfunc $phpfunc, array $cookie, array $options = array())
    {
        $this->phpfunc = $phpfunc;
        $this->cookie = $cookie;
        $this->options = array(
            'path' => '/',
            'domain' => '',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax',
        );
        foreach ($this->options as $key => $val) {
            if (array_key_exists($key, $options)) {
                $this->options[$key] = $options[$key];
            }
        }
    }

    /**
     *
     * Reads a cookie value.
     *
     * @param string $name The cookie name.
     *
     * @param mixed $alt The value to return if the cookie is absent.
     *
     * @return mixed
     *
     */
    public function get($name, $alt = null)
    {
        if (isset($this->cookie[$name])) {
            return $this->cookie[$name];
        }
        return $alt;
    }

    /**
     *
     * Writes a cookie, applying the hardened defaults.
     *
     * @param string $name The cookie name.
     *
     * @param string $value The cookie value.
     *
     * @param int $expires The Unix expiry time.
     *
     * @return bool
     *
     */
    public function set($name, $value, $expires)
    {
        $this->cookie[$name] = $value;
        $options = $this->options;
        $options['expires'] = $expires;
        return $this->phpfunc->setcookie($name, $value, $options);
    }

    /**
     *
     * Deletes a cookie.
     *
     * @param string $name The cookie name.
     *
     * @return bool
     *
     */
    public function delete($name)
    {
        unset($this->cookie[$name]);
        $options = $this->options;
        $options['expires'] = 1;
        return $this->phpfunc->setcookie($name, '', $options);
    }
}
