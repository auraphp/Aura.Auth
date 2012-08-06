<?php


namespace Aura\Auth\Adapter;

class MockClosure
{

    public function __invoke($username, $password)
    {
        if ('janedoe' == $username && '12345' == $password) {
            return ['username' => $username];
        }

        return false;
    }
}

class MockClosure_NoInvoke
{

    
}