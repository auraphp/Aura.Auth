<?php
namespace Aura\Auth\Handler;

use Aura\Auth\Adapter\AdapterInterface;
use Aura\Auth\User;
use Aura\Auth\Status;

class LogoutHandler
{
    public function __construct(
        User $user,
        AdapterInterface $adapter
    ) {
        $this->user = $user;
        $this->adapter = $adapter;
    }

    public function __invoke($status = Status::ANON)
    {
        $this->adapter->logout($this->user, $status);
        $this->user->forceLogout($status);
    }
}
