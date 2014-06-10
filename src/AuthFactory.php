<?php
namespace Aura\Auth;

class AuthFactory
{
    public function newInstance($type)
    {
        $type = ucfirst(strtolower($type));
        $adapter = "Aura\Auth\Adapter\\{$type}";
        return new Auth(new $adapter, new Session);
    }
}
