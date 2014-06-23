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
use Aura\Auth\Status;

/**
 *
 * Logout handler
 *
 * @package Aura.Auth
 *
 */
class LogoutHandler
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
     * Logout user
     *
     * @see User::forceLogout
     *
     * @see AdapterInterface::logout
     *
     * @param string $status see Status class
     *
     */
    public function __invoke($status = Status::ANON)
    {
        $this->adapter->logout($this->user, $status);
        $this->user->forceLogout($status);
    }
}
