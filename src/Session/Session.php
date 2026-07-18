<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
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
class Session implements SessionInterface
{
    /**
     *
     * A copy of the $_COOKIE array.
     *
     * @var array
     *
     */
    protected $cookie;

    /**
     *
     * Constructor.
     *
     * @param array $cookie A copy of the $_COOKIE array.
     *
     */
    public function __construct(array $cookie)
    {
        $this->cookie = $cookie;
    }

    /**
     *
     * Starts a session.
     *
     * @return bool
     *
     */
    public function start(): bool
    {
        return session_start();
    }

    /**
     *
     * Resumes a previously-started session.
     *
     * @return bool
     *
     */
    public function resume(): bool
    {
        if (session_id() !== '') {
            return true;
        }

        if (isset($this->cookie[session_name()])) {
            return $this->start();
        }

        return false;
    }

    /**
     *
     * Regenerates a session ID.
     *
     * @return bool
     *
     */
    public function regenerateId(): bool
    {
        return session_regenerate_id(true);
    }
}
