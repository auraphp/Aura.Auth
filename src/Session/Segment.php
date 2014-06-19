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
 * A $_SESSION segment; it attaches to $_SESSION lazily (i.e., only after a
 * session becomes available.)
 *
 * @package Aura.Auth
 *
 */
class Segment implements SegmentInterface
{
    /**
     *
     * @var string
     *
     */
    protected $name;

    /**
     *
     * @param bool $segment
     *
     */
    public function __construct($name = 'Aura\Auth\Auth')
    {
        $this->name = $name;
    }

    /**
     *
     * @param mixed $key
     *
     * @param mixed $alt
     *
     * @return mixed
     *
     */
    public function get($key, $alt = null)
    {
        if (isset($_SESSION[$this->name][$key])) {
            return $_SESSION[$this->name][$key];
        }

        return $alt;
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
    public function set($key, $val)
    {
        if (! isset($_SESSION)) {
            return;
        }

        $_SESSION[$this->name][$key] = $val;
    }
}
