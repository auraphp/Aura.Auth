<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @package Aura.Auth
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Auth\Adapter;

/**
 *
 * Authentication adapter
 *
 * @package Aura.Auth
 *
 */
abstract class AbstractAdapter implements AdapterInterface
{
    /**
     *
     * @var string
     *
     */
    protected $user;

    /**
     *
     * @var array
     *
     */
    protected $info = array();

    /**
     *
     * @var string
     *
     */
    protected $error;

    /**
     *
     * A verifier for passwords.
     *
     * @var VerifierInterface
     *
     */
    protected $verifier;

    /**
     *
     * Return object of type VerifierInterface
     *
     * @return VerifierInterface
     *
     */
    public function getVerifier()
    {
        return $this->verifier;
    }

    /**
     *
     * @param mixed $cred
     *
     * @return bool
     *
     */
    abstract public function login($cred);

    /**
     *
     * Logout a user resetting all the values
     *
     * @return bool
     *
     */
    public function logout($user, array $info = array())
    {
        $this->reset();
        return true;
    }

    /**
     *
     * @return $user
     *
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     *
     * Return user details
     *
     * @return array
     *
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     *
     * @return string
     *
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     *
     * Reset the user information and errors
     *
     * @return void
     *
     */
    protected function reset()
    {
        $this->user = null;
        $this->info = array();
        $this->error = null;
    }
}
