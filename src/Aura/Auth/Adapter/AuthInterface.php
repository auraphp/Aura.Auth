<?php
/**
 * 
 * This file is part of the Aura project for PHP.
 * 
 * @license http://opensource.org/licenses/bsd-license.php BSD
 * 
 */
namespace Aura\Auth\Adapter;

use Aura\Auth\UserFactory;

/**
 * 
 * Interface for an Auth adapter.
 * 
 * @package Aura.Auth
 * 
 */
interface AuthInterface
{
    /**
     * 
     * Authenticate a user.
     * 
     * @param array $opts A list of optional parameters to pass to 
     * the Auth adapter.
     * 
     * @return Aura\Auth\User|boolean
     * 
     */
    public function authenticate(array $opts = []);
}