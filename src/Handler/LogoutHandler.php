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
namespace Aura\Auth\Handler;

use Aura\Auth\Adapter\AdapterInterface;
use Aura\Auth\Session\SessionInterface;
use Aura\Auth\Status;
use Aura\Auth\User;

/**
 *
 * Logout handler
 *
 * @package Aura.Auth
 *
 */
class LogoutHandler extends AbstractHandler
{
    /**
     *
     * Logout user
     *
     * @see User::forceLogout
     *
     * @see AdapterInterface::logout
     *
     * @param string $status see Status class
     *
     */
    public function logout($status = Status::ANON)
    {
        $this->adapter->logout($this->user, $status);
        $this->forceLogout($status);
    }
}
