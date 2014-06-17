<?php
namespace Aura\Auth\Session;

interface SegmentInterface
{
    public function __get($key);
    public function __set($key, $val);
    public function __isset($key);
    public function __unset($key);
}
