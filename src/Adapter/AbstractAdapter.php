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
use Aura\Auth\Auth;

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
     * @param Auth $auth
     *
     * @param string $status @see Status
     *
     * @return bool
     *
     */
    public function logout(Auth $auth, $status = Status::ANON)
    {
        // do nothing
    }

    /**
     *
     * Resume logged in session
     *
     * @param Auth $auth
     *
     */
    public function resume(Auth $auth)
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
