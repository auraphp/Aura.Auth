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

    public function resume(User $user)
    {
        $logout = $user->resumeSession()
               && ($user->isIdle() || $user->isExpired());

        if ($logout) {
            $this->logout($user, $user->getStatus());
        }
    }

    /**
     *
     * @param mixed $cred
     *
     * @return bool
     *
     */
    abstract public function login(User $user, $cred);

    /**
     *
     * Logout a user resetting all the values
     *
     * @return bool
     *
     */
    public function logout(User $user, $status = Status::ANON)
    {
        $user->forceLogout($status);
    }

    /**
     *
     * @param array $creds
     *
     * @return bool
     *
     */
    protected function checkCredentials(&$creds)
    {
        if (empty($creds['username'])) {
            throw new Exception\UsernameMissing;
        }

        if (empty($creds['password'])) {
            throw new Exception\PasswordMissing;
        }
    }
}
