<?php
/**
 *
 * This file is part of the Aura project for PHP.
 *
 * @license http://opensource.org/licenses/MIT-license.php MIT
 *
 */
namespace Aura\Auth;

/**
 *
 * Constants for authentication statuses.
 *
 * @package Aura.Auth
 *
 */
class Status
{
    /**
     *
     * The user is anonymous/unauthenticated.
     *
     * @const string
     *
     */
    const ANON = 'ANON';

    /**
     *
     * The max time for authentication has expired.
     *
     * @const string
     *
     */
    const EXPIRED = 'EXPIRED';

    /**
     *
     * The authenticated user has been idle for too long.
     *
     * @const string
     *
     */
    const IDLE = 'IDLE';

    /**
     *
     * The user is authenticated and has not idled or expired.
     *
     * @const string
     *
     */
    const VALID = 'VALID';

    /**
     *
     * The user was re-authenticated from a "remember me" token and did not
     * pass credentials this session. Treat as lower privilege than VALID:
     * block password changes, administrative actions, and other sensitive
     * operations until the user re-authenticates.
     *
     * @const string
     *
     */
    const REMEMBERED = 'REMEMBERED';
}
