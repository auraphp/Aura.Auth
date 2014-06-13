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
     * @param void
     *
     * @return boolean
     *
     */
    public function authenticate();
}
