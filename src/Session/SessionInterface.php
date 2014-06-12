<?php
namespace Aura\Auth\Session;

interface SessionInterface
{
    public function __get($key);
    public function __set($key, $val);
    public function __isset($key);
    public function __unset($key);
    public function regenerateId();
}
