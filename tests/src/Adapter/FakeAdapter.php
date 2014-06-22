<?php
namespace Aura\Auth\Adapter;

use Aura\Auth\User;
use Aura\Auth\Exception;

class FakeAdapter extends AbstractAdapter
{
    protected $accounts = array();

    public function __construct(array $accounts = array())
    {
        $this->accounts = $accounts;
    }

    public function login(array $cred)
    {
        return array($cred['username'], array());
    }
}
