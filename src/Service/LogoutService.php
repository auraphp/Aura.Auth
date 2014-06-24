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
namespace Aura\Auth\Service;

use Aura\Auth\Adapter\AdapterInterface;
use Aura\Auth\Session\SessionInterface;
use Aura\Auth\Status;
use Aura\Auth\Auth;

/**
 *
 * Logout handler
 *
 * @package Aura.Auth
 *
 */
class LogoutService extends AbstractService
{
    /**
     *
     * Logout user
     *
     * @see Auth::forceLogout
     *
     * @see AdapterInterface::logout
     *
     * @param string $status see Status class
     *
     */
    public function logout($status = Status::ANON)
    {
        $this->adapter->logout($this->auth, $status);
        $this->forceLogout($status);
    }
}
