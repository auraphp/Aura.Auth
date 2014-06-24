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
 * Login handler
 *
 * @package Aura.Auth
 *
 */
class LoginHandler extends AbstractHandler
{
    protected $adapter;

    protected $session;

    protected $user;

    /**
     *
     * Login user
     *
     * @see AdapterInterface::login
     *
     * @see User::forceLogin
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
