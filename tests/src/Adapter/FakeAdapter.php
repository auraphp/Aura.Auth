<?php
namespace Aura\Auth\Adapter;

use Aura\Auth\User;

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

    public function logout(User $user)
    {
        $data = $user->getData();
        if (isset($data['logout_error'])) {
            $this->error = 'Triggered logout error.';
            return false;
        }

        return parent::logout($user);
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
        if (! $this->user) {
            $this->error = 'Username missing.';
            return false;
        }

        if (empty($cred['password'])) {
            $this->error = 'Password missing.';
            return false;
        }

        $password = $cred['password'];
        if ($this->accounts[$this->user] !== $password) {
            $this->error = 'Password mismatch.';
            return false;
        }

        $this->info = array('adapter' => 'fake');
        return true;
    }
}
