<?php
namespace Aura\Auth;

abstract class AbstractAdapter implements AdapterInterface
{
    protected $error;

    public function logout($user, array $info = array())
    {
        return true;
    }
}
