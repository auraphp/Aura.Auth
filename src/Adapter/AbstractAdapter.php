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

use Aura\Auth\Exception;
use Aura\Auth\Status;
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
    abstract public function login(array $cred);

    /**
     *
     * Logout a user resetting all the values
     *
     * @return bool
     *
     */
    public function logout(User $user, $status = Status::ANON)
    {
        // do nothing
    }

    public function resume(User $user)
    {
        // do nothing
    }

    /**
     *
     * @param array $cred
     *
     * @return bool
     *
     */
    protected function checkCredentials(&$cred)
    {
        if (empty($cred['username'])) {
            throw new Exception\UsernameMissing;
        }

        if (empty($cred['password'])) {
            throw new Exception\PasswordMissing;
        }
    }
}
