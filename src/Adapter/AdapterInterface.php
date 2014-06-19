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
     * @param mixed $cred
     *
     * @return bool
     *
     */
    public function login($cred);

    /**
     *
     * Logout a user resetting all the values
     *
     * @return bool
     *
     */
    public function logout(User $user);

    /**
     *
     * @return $user
     *
     */
    public function getName();

    /**
     *
     * Return user details
     *
     * @return array
     *
     */
    public function getData();

    /**
     *
     * @return string
     *
     */
    public function getError();
}
