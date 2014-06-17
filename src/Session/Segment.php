<?php
namespace Aura\Auth\Session;

class Segment implements SegmentInterface
{
    protected $segment;

    public function __construct($segment = 'Aura\Auth\Auth')
    {
        $this->segment = $segment;
    }

    public function __get($key)
    {
        if (isset($_SESSION[$this->segment][$key])) {
            return $_SESSION[$this->segment][$key];
        }
    }

    public function __set($key, $val)
    {
        if (! isset($_SESSION)) {
            return;
        }

        $_SESSION[$this->segment][$key] = $val;
    }

    public function __isset($key)
    {
        return isset($_SESSION[$this->segment][$key]);
    }

    public function __unset($key)
    {
        unset($_SESSION[$this->segment][$key]);
    }
}
