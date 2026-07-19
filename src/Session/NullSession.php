<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/MIT-license.php MIT
 *
 */
namespace Aura\Auth\Session;

/**
 *
 * Session manager.
 *
 * @package Aura.Auth
 *
 */
class NullSession implements SessionInterface
{
    /**
     *
     * Start Session
     *
     * @return bool
     *
     */
    public function start(): bool
    {
        return true;
    }

    /**
     *
     * Resume previous session
     *
     * @return bool
     *
     */
    public function resume(): bool
    {
        return false;
    }

    /**
     *
     * Re generate session id
     *
     * @return bool
     *
     */
    public function regenerateId(): bool
    {
        return true;
    }
}
