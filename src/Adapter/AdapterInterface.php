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
    public function resume(User $user);

    /**
     *
     * @param User $user
     *
     * @param mixed $cred
     *
     * @return null
     *
     */
    public function login(array $cred);

    /**
     *
     * Logout a user resetting all the values
     *
     * @return null
     *
     */
    public function logout(User $user);
}
