<?php
namespace Aura\Auth\Session;

class SessionObject extends AbstractSession
{
    public function __get($key)
    {
        if (isset($this->data->$key)) {
            return $this->data->$key;
        }
    }

    public function __set($key, $val)
    {
        $this->data->$key = $val;
    }

    public function __isset($key)
    {
        return isset($this->data->$key);
    }

    public function __unset($key)
    {
        unset($this->data->$key);
    }
}
