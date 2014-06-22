<?php
namespace Aura\Auth\Handler;

use Aura\Auth\Adapter\AdapterInterface;
use Aura\Auth\User;

class LoginHandler
{
    public function __construct(
        User $user,
        AdapterInterface $adapter
    ) {
        $this->user = $user;
        $this->adapter = $adapter;
    }

    public function __invoke($cred)
    {
        list($name, $data) = $this->adapter->login($cred);
        $this->user->forceLogin($name, $data);
    }
}
