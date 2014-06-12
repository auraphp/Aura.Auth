<?php
namespace Aura\Auth\Session;

class SessionArray extends AbstractSession
{
    public function __get($key)
    {
        if (isset($this->session[$key])) {
            return $this->session[$key];
        }
    }

    public function __set($key, $val)
    {
        $this->session[$key] = $val;
    }

    public function __isset($key)
    {
        return isset($this->session[$key]);
    }

    public function __unset($key)
    {
        unset($this->session[$key]);
    }
}
