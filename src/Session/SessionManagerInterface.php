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
namespace Aura\Auth\Session;

/**
 *
 * Authentication manager.
 *
 * @package Aura.Auth
 *
 */
interface SessionManagerInterface
{
    /**
     *
     * start only if not already started
     *
     */
    public function start();

    /**
     *
     * start only if none already exists
     *
     */
    public function resume();

    /**
     *
     * regenerate the session id
     *
     */
    public function regenerateId();
}
