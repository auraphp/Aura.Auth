<?php
namespace Aura\Auth\Session;

class Segment implements SegmentInterface
{
    protected $name;

    public function __construct($name = 'Aura\Auth\Auth')
    {
        $this->name = $name;
    }

    public function get($key, $alt = null)
    {
        if (isset($_SESSION[$this->name][$key])) {
            return $_SESSION[$this->name][$key];
        }

        return $alt;
    }

    public function set($key, $val)
    {
        if (! isset($_SESSION)) {
            return;
        }

        $_SESSION[$this->name][$key] = $val;
    }
}
