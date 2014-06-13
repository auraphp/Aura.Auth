<?php
namespace Aura\Auth\Handler;

use Aura\Auth\Auth;
use Aura\Auth\Adapter\AdapterInterface;

class AbstractHandler
{
    protected $auth;

    protected $adapter;

    public function __construct(
        Auth $auth,
        AdapterInterface $adapter
    ) {
        $this->auth = $auth;
        $this->adapter = $adapter;
    }

    public function getAuth()
    {
        return $this->auth;
    }

    public function getAdapter()
    {
        return $this->adapter;
    }

    public function getError()
    {
        return $this->adapter->getError();
    }
}
