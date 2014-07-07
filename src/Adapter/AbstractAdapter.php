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
     * @param mixed $input
     *
     * @return bool
     *
     */
    abstract public function login(array $input);

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
     * @param array $input
     *
     * @return bool
     *
     */
    protected function checkInput($input)
    {
        if (empty($input['username'])) {
            throw new Exception\UsernameMissing;
        }

        if (empty($input['password'])) {
            throw new Exception\PasswordMissing;
        }
    }
}
