<?php
namespace Aura\Auth\Adapter;

abstract class AbstractAdapter implements AdapterInterface
{
    protected $user;

    protected $info = array();

    protected $error;

    /**
     *
     * A verifier for passwords.
     *
     * @var VerifierInterface
     *
     */
    protected $verifier;

    public function getVerifier()
    {
        return $this->verifier;
    }

    abstract public function login($cred);

    public function logout($user, array $info = array())
    {
        $this->reset();
        return true;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getInfo()
    {
        return $this->info;
    }

    public function getError()
    {
        return $this->error;
    }

    protected function reset()
    {
        $this->user = null;
        $this->info = array();
        $this->error = null;
    }
}
