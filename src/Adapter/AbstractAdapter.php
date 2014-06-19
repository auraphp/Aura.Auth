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

use Aura\Auth\User;

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
    protected $name;

    /**
     *
     * @var array
     *
     */
    protected $data = array();

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
    public function logout(User $user)
    {
        $this->reset();
        return true;
    }

    /**
     *
     * @return string
     *
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     *
     * Return user details
     *
     * @return array
     *
     */
    public function getData()
    {
        return $this->data;
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
