<?php
namespace Aura\Auth\Session;

class SessionDataObject implements SessionDataInterface
{
    protected $object;

    public function __construct($object)
    {
        $this->object = $object;
    }

    public function __get($key)
    {
        if (isset($this->object->$key)) {
            return $this->object->$key;
        }
    }

    public function __set($key, $val)
    {
        $this->object->$key = $val;
    }

    public function __isset($key)
    {
        return isset($this->object->$key);
    }

    public function __unset($key)
    {
        unset($this->object->$key);
    }
}
