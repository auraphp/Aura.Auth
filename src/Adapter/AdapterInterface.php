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

use Aura\Auth\Auth;

/**
 *
 * Abstract Authentication Storage.
 *
 * @package Aura.Auth
 *
 */
interface AdapterInterface
{
    /**
     *
     * Resumes a previous session.
     *
     */
    public function resume(Auth $auth);

    /**
     *
     * @param array $cred
     *
     * @return null
     *
     */
    public function login(array $cred);

    /**
     *
     * Logout a user resetting all the values
     *
     * @param Auth $auth
     *
     * @return null
     *
     */
    public function logout(Auth $auth);
}
