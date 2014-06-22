<?php
namespace Aura\Auth;

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
}