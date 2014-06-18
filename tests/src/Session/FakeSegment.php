<?php
namespace Aura\Auth\Session;

class FakeSegment implements SegmentInterface
{
    public function __get($key)
    {
        if (isset($this->$key)) {
            return $this->$key;
        }
    }

    public function __set($key, $val)
    {
        $this->$key = $val;
    }

    public function __isset($key)
    {
        return isset($this->$key);
    }

    public function __unset($key)
    {
        unset($this->$key);
    }
}
