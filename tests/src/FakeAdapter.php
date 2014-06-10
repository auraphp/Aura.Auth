<?php
namespace Aura\Auth;

use Aura\Auth\Adapter\AbstractAdapter;

class FakeAdapter extends AbstractAdapter
{
    protected $accounts = array();

    public function __construct(array $accounts = array())
    {
        $this->accounts = $accounts;
    }

    public function login($cred)
    {
        $this->reset();
        return $this->checkUsername($cred)
            && $this->checkPassword($cred);
    }

    protected function checkUsername($cred)
    {
        if (empty($cred['username'])) {
            $this->error = 'Username missing.';
            return false;
        }

        $username = $cred['username'];
        if (empty($this->accounts[$username])) {
            $this->error = 'No such account.';
            return false;
        }

        $this->user = $username;
        return true;
    }

    protected function checkPassword($cred)
    {
        if (empty($cred['password'])) {
            $this->error = 'Password missing.';
            return false;
        }

        $password = $cred['password'];
        if ($this->accounts[$username] !== $password) {
            $this->error = 'Password mismatch.';
            return false;
        }

        $this->info = array('adapter' => 'fake');
        return true;
    }
}
