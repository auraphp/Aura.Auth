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
 * Login handler
 *
 * @package Aura.Auth
 *
 */
class LoginService extends AbstractService
{
    /**
     *
     * Login user
     *
     * @see AdapterInterface::login
     *
     * @see Auth::forceLogin
     *
     * @param array $cred
     *
     * @return void
     */
    public function login(array $cred)
    {
        list($name, $data) = $this->adapter->login($cred);
        $this->forceLogin($name, $data);
    }
}
