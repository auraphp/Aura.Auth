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
use Aura\Auth\User;

/**
 *
 * Login handler
 *
 * @package Aura.Auth
 *
 */
class LoginHandler
{
    /**
     *
     *  @param User $user
     *
     *  @param AdapterInterface $adapter
     *
     */
    public function __construct(
        User $user,
        AdapterInterface $adapter
    ) {
        $this->user = $user;
        $this->adapter = $adapter;
    }

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
    public function __invoke($cred)
    {
        list($name, $data) = $this->adapter->login($cred);
        $this->user->forceLogin($name, $data);
    }
}
