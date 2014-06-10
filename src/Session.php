<?php
namespace Aura\Auth;

class Session implements SessionInterface
{
    protected $segment;

    public function __construct($segment)
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
        $_SESSION[$this->segment][$key] = $val;
    }

    public function __isset($key)
    {
        $_SESSION[$this->segment][$key] = $val;
    }

    public function __unset($key)
    {
        unset($_SESSION[$this->segment][$key]);
    }

    public function start()
    {
        // only start if not already started
        if (! session_id()) {
            session_start();
        }
    }

    public function regenerateId()
    {
        session_regenerate_id(true);
    }

    public function destroy()
    {
        session_destroy();
    }
}
