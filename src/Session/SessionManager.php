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
 * Session manager.
 *
 * @package Aura.Auth
 *
 */
class SessionManager implements SessionManagerInterface
{
    /**
     * start
     *
     * @var mixed
     *
     */
    protected $start;

    /**
     * resume
     *
     * @var mixed
     *
     */
    protected $resume;

    /**
     * regenerate_id
     *
     * @var mixed
     *
     */
    protected $regenerate_id;

    /**
     *
     * @param array $cookies
     *
     * @param callable $start
     *
     * @param callable $resume
     *
     * @param callable $regenerate_id
     *
     */
    public function __construct(
        array $cookies,
        $start = null,
        $resume = null,
        $regenerate_id = null
    ) {
        $this->cookies = $cookies;
        $this->start = $start;
        $this->resume = $resume;
        $this->regenerate_id = $regenerate_id;
    }

    /**
     *
     * Start Session
     *
     * @return bool
     *
     */
    public function start()
    {
        if ($this->start) {
            return call_user_func($this->start);
        }

        if (session_id() === '') {
            return session_start();
        }

        return false;
    }

    /**
     *
     * Start / resume session
     *
     * @return bool
     *
     */
    public function resume()
    {
        if ($this->resume) {
            return call_user_func($this->resume);
        }

        if (isset($this->cookies[session_name()])) {
            return $this->start();
        }

        return false;
    }

    /**
     *
     * Re generate session id
     *
     * @return mixed
     *
     */
    public function regenerateId()
    {
        if ($this->regenerate_id) {
            return call_user_func($this->regenerate_id);
        }

        return session_regenerate_id(true);
    }
}
